<?php
// Temporary FarmOS data for map testing
// This will be replaced once OAuth is working

// Sample geometry data for Lincoln, UK area (approximate farm location)
$sampleFarmData = [
    'type' => 'FeatureCollection',
    'features' => [
        [
            'type' => 'Feature',
            'properties' => [
                'name' => 'Main Field',
                'id' => 'field_1',
                'status' => 'active'
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [-0.520, 53.210],
                    [-0.515, 53.210],
                    [-0.515, 53.215],
                    [-0.520, 53.215],
                    [-0.520, 53.210]
                ]]
            ]
        ],
        [
            'type' => 'Feature',
            'properties' => [
                'name' => 'North Field',
                'id' => 'field_2',
                'status' => 'active'
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [-0.510, 53.220],
                    [-0.505, 53.220],
                    [-0.505, 53.225],
                    [-0.510, 53.225],
                    [-0.510, 53.220]
                ]]
            ]
        ]
    ]
];

echo json_encode($sampleFarmData, JSON_PRETTY_PRINT);
?>
