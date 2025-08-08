# Orbitools WordPress Plugin

A comprehensive WordPress plugin providing advanced layout blocks, responsive controls, and typography management for modern WordPress themes.

## 🚀 Features

### Layout Blocks System
- **Collection Block**: Container for organizing content layouts
- **Entry Block**: Individual content items with flexible sizing
- **Spacer Block**: Responsive spacing control with breakpoint support

### Responsive Design System
- Breakpoint-based controls (sm: 810px, md: 1080px, lg: 1360px, xl: 1600px)
- Theme.json integration for consistent spacing values
- CSS utility class generation for responsive behavior

### Typography Presets
- Centralized typography management
- Theme-configurable font presets
- Gutenberg editor integration

### Security Features
- OWASP Top 10 compliance
- Package integrity verification with SHA256 checksums
- Comprehensive security logging system
- Input sanitization and capability checks

## 📁 Project Structure

```
orbitools/
├── src/blocks/                    # WordPress Gutenberg blocks
│   ├── collection/               # Collection container block
│   ├── entry/                   # Entry item block  
│   ├── spacer/                  # Responsive spacer block
│   └── utils/                   # Shared utilities
│       ├── responsive-controls.tsx  # Responsive control system
│       └── config-reader.ts        # Configuration management
├── modules/                      # Plugin modules
│   ├── Layout_Blocks/           # Block registration
│   └── Typography_Presets/      # Typography system
├── config/                      # Configuration files
│   └── defaults.json            # Plugin default settings
├── build/                       # Compiled assets
└── webpack.*.js                 # Build configuration
```

## 🛠 Development

### Building the Plugin
```bash
# Install dependencies
npm install

# Build all assets
npm run build

# Build only blocks
npm run build:blocks

# Build only assets  
npm run build:assets
```

### Block Development
Each block follows WordPress standards:
- `block.json` - Block metadata and registration
- `index.tsx` - Block registration and imports
- `edit.tsx` - Editor interface
- `save.tsx` - Frontend output
- `*.scss` - Styling (editor and frontend)

### Configuration System
The plugin uses a hierarchical configuration system:
1. **Theme config**: `/wp-content/themes/[theme]/config/orbitools.json` (priority)
2. **Plugin defaults**: `/config/defaults.json` (fallback)

## 🎨 Block Usage

### Collection Block
Container for organizing layouts with different systems:
- **Row Layout**: Horizontal arrangement
- **Grid Layout**: 5-column or 12-column systems
- **Responsive**: Different layouts per breakpoint

### Entry Block  
Individual content items within Collections:
- **Column Width**: Custom sizing in grid layouts
- **Spacing Controls**: Theme-based gap sizing
- **Content**: Supports all WordPress blocks

### Spacer Block
Responsive spacing with theme integration:
- **Height Control**: Uses theme.json spacing values
- **Responsive**: Different heights per breakpoint  
- **Special Values**: Default, None, [Theme Sizes], Fill
- **Output**: Single `<div>` with height CSS classes

## 🔧 Technical Details

### WordPress Integration
- **WordPress 6.0+**: Modern block editor features
- **Theme.json Support**: Automatic spacing and typography integration
- **PHP 8.0+**: Modern PHP features and performance

### Build System
- **Webpack**: Asset compilation and optimization
- **TypeScript**: Type-safe development
- **SCSS**: Advanced styling capabilities
- **WordPress Scripts**: Standard WordPress build tools

### Security
- **Nonce Verification**: All AJAX requests protected
- **Capability Checks**: Proper permission validation
- **Input Sanitization**: All user input cleaned
- **Package Verification**: SHA256 integrity checks for updates

## 📚 Development Notes

**Important**: See `CLAUDE.md` for detailed development guidelines, patterns, and lessons learned. This file contains crucial information about:

- Systematic API change procedures
- WordPress block development patterns  
- Common pitfalls and how to avoid them
- Quality assurance checklists
- Technical implementation details

### Key Development Rules
1. **Systematic Changes**: When fixing core APIs, update ALL instances across the codebase
2. **Consistent Patterns**: Use established patterns for `useSettings`, responsive controls, etc.
3. **Test Thoroughly**: Verify all related functionality, not just new features
4. **Clean Codebase**: Remove unused files and maintain consistent structure

## 🐛 Troubleshooting

### Common Issues
1. **Spacing Controls Missing**: Check `useSettings('spacing.spacingSizes')` format (string, not array)
2. **Build Errors**: Ensure all files are properly imported and TypeScript types are correct
3. **Block Not Showing**: Verify webpack config and PHP registration in `Layout_Blocks.php`

### Debug Steps
1. Check browser console for JavaScript errors
2. Verify build output in `/build/blocks/` directory
3. Confirm block registration in WordPress admin
4. Test with default theme to isolate theme conflicts

## 📄 License

This plugin is proprietary software developed for specific WordPress implementations.

---

*For detailed development guidelines and technical patterns, always refer to `CLAUDE.md` before making changes.*