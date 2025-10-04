/**
 * Succession Planning JavaScript Module
 * Handles UI interactions, calculations, and API calls for succession planning
 */

class SuccessionPlanner {
    constructor(config = {}) {
        this.cropTypes = config.cropTypes || [];
        this.cropVarieties = config.cropVarieties || [];
        this.availableBeds = config.availableBeds || [];
        this.currentSuccessionPlan = null;
        this.isDragging = false;
        this.cacheBuster = Date.now();
        this.apiBase = window.location.origin + '/admin/farmos/succession-planning';
        this.farmosBase = config.farmosBase || '';
        this.cropId = null;
    }

    /**
     * Initialize the succession planner
     */
    async initialize() {
        console.log('🚀 Initializing Succession Planner Module');

        // Setup event listeners
        this.setupEventListeners();

        // Initialize UI components
        this.initializeUI();

        // Restore saved state
        this.restorePlannerState();

        // Test connections
        await this.testConnections();

        console.log('✅ Succession Planner initialized');
    }

    /**
     * Setup event listeners for the succession planner
     */
    setupEventListeners() {
        console.log('📋 Setting up event listeners');

        // Crop selection change
        const cropSelect = document.getElementById('cropSelect');
        if (cropSelect) {
            cropSelect.addEventListener('change', (e) => this.handleCropSelection(e.target.value));
        }

        // Variety selection change
        const varietySelect = document.getElementById('varietySelect');
        if (varietySelect) {
            varietySelect.addEventListener('change', (e) => this.handleVarietySelection(e.target.value));
        }

        // Season/Year handlers
        this.setupSeasonYearHandlers();

        // AI status monitoring
        this.setupAIStatusMonitoring();

        // Harvest window selector
        this.initializeHarvestWindowSelector();
    }

    /**
     * Handle crop selection change
     */
    handleCropSelection(cropId) {
        console.log('🌱 Crop selected:', cropId);
        this.cropId = cropId;

        // Update the crop select element to reflect the selection
        const cropSelect = document.getElementById('cropSelect');
        if (cropSelect) cropSelect.value = cropId;

        this.updateVarieties();
        this.savePlannerState();
        this.updateSuccessionImpact();

        // Check if there are varieties available for this crop
        const varietySelect = document.getElementById('varietySelect');
        const hasVarieties = varietySelect && varietySelect.options.length > 1; // More than just "Select variety..."

        // If no varieties available, initialize harvest window immediately
        // Otherwise, wait for variety selection
        if (!hasVarieties) {
            this.initializeBasicHarvestWindow(cropId);
        }
    }

    /**
     * Handle variety selection change
     */
    async handleVarietySelection(varietyId) {
        console.log('🔄 Variety selected:', varietyId);
        this.savePlannerState();
        this.updateSuccessionImpact();
        
        // Call the global handleVarietySelection function to display variety info
        if (typeof window.handleVarietySelection === 'function') {
            await window.handleVarietySelection(varietyId);
        }

        // Initialize harvest window now that we have crop + variety (or just crop)
        if (this.cropId) {
            this.initializeBasicHarvestWindow(this.cropId, varietyId);
        }

        // Removed automatic AI harvest window calculation - now manual only
    }

    /**
     * Update varieties dropdown based on selected crop
     */
    updateVarieties() {
        const varietySelect = document.getElementById('varietySelect');
        if (!varietySelect) return;

        if (!this.cropId) {
            varietySelect.innerHTML = '<option value="">Select crop first...</option>';
            return;
        }

        const filteredVarieties = this.cropVarieties.filter(v => v.parent_id === this.cropId);
        varietySelect.innerHTML = '<option value="">Select variety...</option>' +
            filteredVarieties.map(v => `<option value="${v.id}">${v.name}</option>`).join('');
    }

    /**
     * Update succession impact
     * Note: This calls the global updateSuccessionImpact function
     */
    updateSuccessionImpact() {
        // Call the global updateSuccessionImpact function
        if (typeof window.updateSuccessionImpact === 'function') {
            window.updateSuccessionImpact();
        } else {
            console.log('📈 SuccessionPlanner: Global updateSuccessionImpact not available yet');
        }
    }

    /**
     * Update harvest window display with retry (in case functions not loaded yet)
     */
    updateHarvestWindowDisplayWithRetry(retryCount = 0) {
        const maxRetries = 10;

        if (typeof updateHarvestWindowDisplay === 'function') {
            updateHarvestWindowDisplay();

            // Also update the drag bar
            if (typeof updateDragBar === 'function') {
                setTimeout(() => updateDragBar(), 100);
            }

            console.log('✅ Harvest window display updated');
        } else if (retryCount < maxRetries) {
            // Retry after a short delay
            setTimeout(() => {
                this.updateHarvestWindowDisplayWithRetry(retryCount + 1);
            }, 100);
            console.log(`⏳ Waiting for harvest window functions to load (attempt ${retryCount + 1}/${maxRetries})`);
        } else {
            console.warn('❌ Harvest window display functions not available after retries');
        }
    }

    /**
     * Initialize basic harvest window display for selected crop and variety
     */
    initializeBasicHarvestWindow(cropId, varietyId = null) {
        if (!cropId) return;

        // Get crop name for fallback logic
        const cropSelect = document.getElementById('cropSelect');
        const cropName = cropSelect ? cropSelect.options[cropSelect.selectedIndex]?.text?.toLowerCase() : '';

        // Get variety name if available
        let varietyName = '';
        if (varietyId) {
            const varietySelect = document.getElementById('varietySelect');
            varietyName = varietySelect ? varietySelect.options[varietySelect.selectedIndex]?.text?.toLowerCase() : '';
        }

        console.log('🌾 Initializing harvest window for:', { cropName, varietyName, cropId, varietyId });

        // Set basic harvest window data based on crop type (and potentially variety)
        const year = new Date().getFullYear();
        let maxStart, maxEnd, userStart, userEnd;

        // Default harvest windows based on crop type
        // TODO: In future, variety-specific adjustments can be added here
        switch (cropName) {
            case 'carrots':
            case 'carrot':
                maxStart = `${year}-05-01`;
                maxEnd = `${year}-12-31`;
                userStart = `${year}-08-01`;
                userEnd = `${year}-10-31`;
                break;
            case 'beets':
            case 'beetroot':
                maxStart = `${year}-06-01`;
                maxEnd = `${year}-12-31`;
                userStart = `${year}-09-01`;
                userEnd = `${year}-11-30`;
                break;
            case 'lettuce':
                maxStart = `${year}-03-01`;
                maxEnd = `${year}-11-30`;
                userStart = `${year}-05-01`;
                userEnd = `${year}-09-30`;
                break;
            case 'radish':
            case 'radishes':
                maxStart = `${year}-04-01`;
                maxEnd = `${year}-10-31`;
                userStart = `${year}-05-15`;
                userEnd = `${year}-08-31`;
                break;
            case 'onion':
            case 'onions':
                maxStart = `${year}-07-01`;
                maxEnd = `${year}-09-30`;
                userStart = `${year}-07-15`;
                userEnd = `${year}-09-15`;
                break;
            case 'brussels sprouts':
            case 'brussels sprout':
            case 'brussel sprouts':
            case 'brussel sprout':
                // Brussels sprouts harvest from October to March/April
                maxStart = `${year}-10-01`;
                maxEnd = `${year + 1}-03-31`;
                userStart = `${year}-11-01`;
                userEnd = `${year + 1}-02-28`;
                break;
            default:
                // Generic default
                maxStart = `${year}-05-01`;
                maxEnd = `${year}-10-31`;
                userStart = `${year}-06-15`;
                userEnd = `${year}-09-15`;
        }

        // Update global harvestWindowData
        if (typeof harvestWindowData !== 'undefined') {
            harvestWindowData.maxStart = maxStart;
            harvestWindowData.maxEnd = maxEnd;
            harvestWindowData.userStart = userStart;
            harvestWindowData.userEnd = userEnd;
            harvestWindowData.aiStart = userStart; // AI defaults to user selection
            harvestWindowData.aiEnd = userEnd;
            console.log('🔄 Updated harvestWindowData:', harvestWindowData);
        } else {
            console.warn('❌ harvestWindowData not available');
        }

        // Update the date inputs
        const harvestStartInput = document.getElementById('harvestStart');
        const harvestEndInput = document.getElementById('harvestEnd');
        if (harvestStartInput) harvestStartInput.value = userStart;
        if (harvestEndInput) harvestEndInput.value = userEnd;

        // Update the harvest window display (with retry if functions not yet loaded)
        this.updateHarvestWindowDisplayWithRetry();

        console.log('🌾 Initialized basic harvest window for:', cropName, varietyName || 'no variety', { maxStart, maxEnd, userStart, userEnd });
    }

    /**
     * Setup season and year handlers
     */
    setupSeasonYearHandlers() {
        const yearEl = document.getElementById('planningYear');
        const seasonEl = document.getElementById('planningSeason');

        if (yearEl) {
            yearEl.addEventListener('change', () => this.savePlannerState());
        }
        if (seasonEl) {
            seasonEl.addEventListener('change', () => this.savePlannerState());
        }
    }

    /**
     * Setup AI status monitoring
     */
    setupAIStatusMonitoring() {
        // Implementation for AI status monitoring
        console.log('🤖 Setting up AI status monitoring');
    }

    /**
     * Initialize harvest window selector
     */
    initializeHarvestWindowSelector() {
        // Implementation for harvest window selector
        console.log('📅 Initializing harvest window selector');
    }

    /**
     * Initialize UI components
     */
    initializeUI() {
        // Initialize variety select
        this.updateVarieties();

        // Initialize harvest bar
        this.initializeHarvestBar();

        // Update export button
        this.updateExportButton();
    }

    /**
     * Initialize harvest bar
     */
    initializeHarvestBar() {
        // Implementation for harvest bar initialization
        console.log('📊 Initializing harvest bar');
    }

    /**
     * Update export button state
     */
    updateExportButton() {
        // Implementation for export button update
        console.log('📤 Updating export button');
    }

    /**
     * Save planner state to localStorage
     */
    savePlannerState() {
        try {
            const state = {
                cropId: this.cropId,
                planningYear: document.getElementById('planningYear')?.value,
                planningSeason: document.getElementById('planningSeason')?.value,
                selectedVariety: document.getElementById('varietySelect')?.value
            };
            localStorage.setItem('successionPlannerState', JSON.stringify(state));
        } catch (error) {
            console.warn('Failed to save planner state:', error);
        }
    }

    /**
     * Restore planner state from localStorage
     */
    restorePlannerState() {
        try {
            const state = JSON.parse(localStorage.getItem('successionPlannerState') || '{}');

            // Only restore planning year and season, not crop selection
            // This keeps the UI clean with placeholders on page load
            if (state.planningYear) {
                const yearEl = document.getElementById('planningYear');
                if (yearEl) yearEl.value = state.planningYear;
            }
            if (state.planningSeason) {
                const seasonEl = document.getElementById('planningSeason');
                if (seasonEl) seasonEl.value = state.planningSeason;
            }

            // Note: We don't restore cropId or selectedVariety to keep clean UI
            // Users can re-select their preferences each session
        } catch (error) {
            console.warn('Failed to restore planner state:', error);
        }
    }

    /**
     * Test connections to external services
     */
    async testConnections() {
        console.log('🔗 Testing connections...');
        // Implementation for connection testing
    }
}