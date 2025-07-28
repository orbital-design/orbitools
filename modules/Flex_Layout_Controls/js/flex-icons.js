/**
 * Flex Layout Controls - Icons
 * 
 * SVG icons for flex layout controls
 */

window.FlexControlsIcons = {
    flexDirectionRow: wp.element.createElement('svg', {
        xmlns: 'http://www.w3.org/2000/svg',
        width: '20',
        height: '20',
        viewBox: '0 0 20 20',
        fill: 'currentColor'
    }, wp.element.createElement('path', {
        d: 'M2 6h16v2H2V6zm0 4h16v2H2v-2z'
    })),
    
    flexDirectionColumn: wp.element.createElement('svg', {
        xmlns: 'http://www.w3.org/2000/svg',
        width: '20',
        height: '20',
        viewBox: '0 0 20 20',
        fill: 'currentColor'
    }, wp.element.createElement('path', {
        d: 'M6 2v16h2V2H6zm4 0v16h2V2h-2z'
    }))
};