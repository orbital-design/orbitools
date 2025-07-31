<?php
/**
 * Generate JavaScript object from SVG icon files
 * Run this script to update the icons in editor-controls.js
 */

$iconDir = __DIR__ . '/icons/flex';
$outputFile = __DIR__ . '/js/alignment-icons.js';

// Define the icon mappings
$iconMappings = [
    'row' => [
        'justifyContent' => [
            'flex-end' => 'row/justify-content/flex-end.svg'
        ]
    ]
];

// Generate the JavaScript object
$jsContent = "// Auto-generated alignment icons from SVG files\n";
$jsContent .= "// Generated on " . date('Y-m-d H:i:s') . "\n\n";
$jsContent .= "const ALIGNMENT_ICONS = {\n";

foreach ($iconMappings as $orientation => $properties) {
    $jsContent .= "    {$orientation}: {\n";
    
    foreach ($properties as $property => $icons) {
        $jsContent .= "        {$property}: {\n";
        
        foreach ($icons as $value => $filePath) {
            $fullPath = $iconDir . '/' . $filePath;
            
            if (file_exists($fullPath)) {
                $svgContent = file_get_contents($fullPath);
                // Clean up the SVG content - remove extra spaces and newlines
                $svgContent = preg_replace('/\s+/', ' ', trim($svgContent));
                $svgContent = str_replace('> <', '><', $svgContent);
                
                // Escape for JavaScript string
                $svgContent = addslashes($svgContent);
                
                $jsContent .= "            '{$value}': '{$svgContent}',\n";
            } else {
                echo "Warning: File not found: {$fullPath}\n";
            }
        }
        
        $jsContent .= "        },\n";
    }
    
    $jsContent .= "    },\n";
}

$jsContent .= "};\n\n";
$jsContent .= "// Export for use in editor-controls.js\n";
$jsContent .= "if (typeof module !== 'undefined' && module.exports) {\n";
$jsContent .= "    module.exports = { ALIGNMENT_ICONS };\n";
$jsContent .= "}\n";

// Write to file
file_put_contents($outputFile, $jsContent);

echo "Generated alignment icons JavaScript file: {$outputFile}\n";
echo "Total icons processed: " . array_sum(array_map(function($props) {
    return array_sum(array_map('count', $props));
}, $iconMappings)) . "\n";
?>