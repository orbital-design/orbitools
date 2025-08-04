export interface LayoutAttributes {
    layoutType: 'row' | 'grid';
    itemWidth: 'fit' | 'equal' | 'custom';
    columnSystem: 5 | 12;
    columnCount: number;
    flexDirection: 'row' | 'column';
    flexWrap: 'nowrap' | 'wrap' | 'wrap-reverse';
    alignItems: 'flex-start' | 'center' | 'flex-end' | 'stretch';
    justifyContent: 'flex-start' | 'center' | 'flex-end' | 'space-between' | 'space-around' | 'space-evenly';
    gapSize?: string;
    restrictContentWidth: boolean;
    stackOnMobile: boolean;
    align?: 'wide' | 'full';
}

export interface LayoutItemAttributes {
    width: string;
    parentItemWidth: 'fit' | 'equal' | 'custom';
}