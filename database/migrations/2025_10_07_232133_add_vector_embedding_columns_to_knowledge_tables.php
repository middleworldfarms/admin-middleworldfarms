<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - adds vector embedding support to knowledge tables
     * 
     * This migration:
     * 1. Copies knowledge data from MySQL to PostgreSQL  
     * 2. Adds vector columns for embeddings (768 dimensions for nomic-embed-text)
     * 3. Creates indexes for fast similarity search
     */
    public function up(): void
    {
        // Get PostgreSQL connection
        $pgsql = DB::connection('pgsql_rag');
        
        // Create companion_planting_knowledge table in PostgreSQL
        $pgsql->statement('
            CREATE TABLE IF NOT EXISTS companion_planting_knowledge (
                id SERIAL PRIMARY KEY,
                primary_crop VARCHAR(255),
                primary_crop_family VARCHAR(255),
                companion_plant VARCHAR(255),
                companion_family VARCHAR(255),
                relationship_type VARCHAR(50),
                benefits TEXT,
                planting_notes TEXT,
                planting_timing VARCHAR(255),
                spacing_notes VARCHAR(255),
                intercrop_type VARCHAR(50),
                days_to_harvest_companion INTEGER,
                quick_crop BOOLEAN DEFAULT FALSE,
                seasonal_considerations TEXT,
                source VARCHAR(255),
                confidence_score INTEGER DEFAULT 5,
                embedding vector(768),
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ');
        
        // Create crop_rotation_knowledge table in PostgreSQL
        $pgsql->statement('
            CREATE TABLE IF NOT EXISTS crop_rotation_knowledge (
                id SERIAL PRIMARY KEY,
                previous_crop VARCHAR(255),
                previous_crop_family VARCHAR(255),
                following_crop VARCHAR(255),
                following_crop_family VARCHAR(255),
                relationship VARCHAR(50),
                benefits TEXT,
                risks TEXT,
                minimum_gap_months INTEGER,
                breaks_disease_cycle BOOLEAN DEFAULT FALSE,
                improves_soil_structure BOOLEAN DEFAULT FALSE,
                fixes_nitrogen BOOLEAN DEFAULT FALSE,
                depletes_nitrogen BOOLEAN DEFAULT FALSE,
                soil_consideration TEXT,
                cover_crop_recommendation TEXT,
                source VARCHAR(255),
                confidence_score INTEGER DEFAULT 5,
                embedding vector(768),
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ');
        
        // Create uk_planting_calendar table in PostgreSQL
        $pgsql->statement('
            CREATE TABLE IF NOT EXISTS uk_planting_calendar (
                id SERIAL PRIMARY KEY,
                crop_name VARCHAR(255) NOT NULL,
                crop_family VARCHAR(255),
                variety_type VARCHAR(255),
                indoor_seed_months VARCHAR(255),
                outdoor_seed_months VARCHAR(255),
                transplant_months VARCHAR(255),
                harvest_months VARCHAR(255),
                frost_hardy BOOLEAN NOT NULL DEFAULT FALSE,
                uk_hardiness_zone VARCHAR(255),
                typical_last_frost DATE,
                typical_first_frost DATE,
                uk_region VARCHAR(255) NOT NULL DEFAULT \'general\',
                seasonal_notes TEXT,
                uk_specific_advice TEXT,
                needs_cloche BOOLEAN NOT NULL DEFAULT FALSE,
                needs_fleece BOOLEAN NOT NULL DEFAULT FALSE,
                needs_polytunnel BOOLEAN NOT NULL DEFAULT FALSE,
                source VARCHAR(255),
                confidence_score INTEGER NOT NULL DEFAULT 5,
                embedding vector(768),
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ');
        
        // Create indexes for fast vector similarity search
        $pgsql->statement('CREATE INDEX IF NOT EXISTS companion_embedding_idx ON companion_planting_knowledge USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
        $pgsql->statement('CREATE INDEX IF NOT EXISTS rotation_embedding_idx ON crop_rotation_knowledge USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
        $pgsql->statement('CREATE INDEX IF NOT EXISTS calendar_embedding_idx ON uk_planting_calendar USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
        
        // Create regular indexes for filtering
        $pgsql->statement('CREATE INDEX IF NOT EXISTS companion_crop_idx ON companion_planting_knowledge(primary_crop)');
        $pgsql->statement('CREATE INDEX IF NOT EXISTS companion_family_idx ON companion_planting_knowledge(primary_crop_family)');
        $pgsql->statement('CREATE INDEX IF NOT EXISTS rotation_previous_idx ON crop_rotation_knowledge(previous_crop)');
        $pgsql->statement('CREATE INDEX IF NOT EXISTS rotation_family_idx ON crop_rotation_knowledge(previous_crop_family)');
        $pgsql->statement('CREATE INDEX IF NOT EXISTS calendar_crop_idx ON uk_planting_calendar(crop_name)');
        $pgsql->statement('CREATE INDEX IF NOT EXISTS calendar_family_idx ON uk_planting_calendar(crop_family)');
        
        // Copy data from MySQL to PostgreSQL (without embeddings for now)
        $this->copyCompanionKnowledge();
        $this->copyRotationKnowledge();
        $this->copyCalendarKnowledge();
    }
    
    /**
     * Copy companion planting knowledge from MySQL to PostgreSQL
     */
    protected function copyCompanionKnowledge(): void
    {
        $mysql = DB::connection('mysql');
        $pgsql = DB::connection('pgsql_rag');
        
        $companions = $mysql->table('companion_planting_knowledge')->get();
        
        foreach ($companions as $companion) {
            $data = (array) $companion;
            // Remove MySQL auto-increment ID, let PostgreSQL generate it
            unset($data['id']);
            
            $pgsql->table('companion_planting_knowledge')->insert($data);
        }
        
        echo "Copied " . count($companions) . " companion planting entries to PostgreSQL\n";
    }
    
    /**
     * Copy crop rotation knowledge from MySQL to PostgreSQL
     */
    protected function copyRotationKnowledge(): void
    {
        $mysql = DB::connection('mysql');
        $pgsql = DB::connection('pgsql_rag');
        
        $rotations = $mysql->table('crop_rotation_knowledge')->get();
        
        foreach ($rotations as $rotation) {
            $data = (array) $rotation;
            unset($data['id']);
            
            $pgsql->table('crop_rotation_knowledge')->insert($data);
        }
        
        echo "Copied " . count($rotations) . " rotation entries to PostgreSQL\n";
    }
    
    /**
     * Copy UK planting calendar from MySQL to PostgreSQL
     */
    protected function copyCalendarKnowledge(): void
    {
        $mysql = DB::connection('mysql');
        $pgsql = DB::connection('pgsql_rag');
        
        $calendar = $mysql->table('uk_planting_calendar')->get();
        
        foreach ($calendar as $entry) {
            $data = (array) $entry;
            unset($data['id']);
            
            $pgsql->table('uk_planting_calendar')->insert($data);
        }
        
        echo "Copied " . count($calendar) . " calendar entries to PostgreSQL\n";
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        $pgsql = DB::connection('pgsql_rag');
        
        $pgsql->statement('DROP TABLE IF EXISTS companion_planting_knowledge CASCADE');
        $pgsql->statement('DROP TABLE IF EXISTS crop_rotation_knowledge CASCADE');
        $pgsql->statement('DROP TABLE IF NOT EXISTS uk_planting_calendar CASCADE');
    }
};
