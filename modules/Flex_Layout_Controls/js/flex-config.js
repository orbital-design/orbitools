/**
 * Flex Layout Controls - Configuration
 *
 * Centralized configuration object - source of truth for all flex controls
 */

window.FlexControlsConfig = {
    columnCount: {
        name: "Columns",
        niceName: "Columns",
        prop: "grid-template-columns",
        control: "RangeControl",
        desc: "Set the number of columns for the layout.",
        default: 3,
        options: {
            min: 1,
            max: 10,
            step: 1
        }
    },
    flexDirection: {
        name: "Flex Direction",
        niceName: "Orientation",
        prop: "flex-direction",
        control: "ToggleGroupControl",
        desc: "Direction of flow for content.",
        default: "row",
        cssMapping: {
            abbrev: "flow",
            classPattern: "flex-flow-{value}",
            skipDefault: false
        },
        options: [
            {
                slug: "row",
                name: "Row",
                niceName: "Horizontal",
                icon: "flexDirectionRow"
            },
            {
                slug: "column",
                name: "Column",
                niceName: "Vertical",
                icon: "flexDirectionColumn"
            }
        ]
    },
    flexWrap: {
        name: "Flex Wrap",
        niceName: "Wrapping",
        prop: "flex-wrap",
        control: "ToggleGroupControl",
        desc: "Controls whether items wrap to new lines.",
        default: "nowrap",
        cssMapping: {
            abbrev: "flow",
            classPattern: "flex-flow-{value}",
            skipDefault: true
        },
        options: [
            {
                slug: "nowrap",
                name: "No Wrap",
                niceName: "No Wrap",
                icon: null
            },
            {
                slug: "wrap",
                name: "Wrap",
                niceName: "Wrap",
                icon: null
            }
        ]
    },
    alignItems: {
        name: "Align Items",
        niceName: {
            row: "Vertical Alignment",
            column: "Horizontal Alignment"
        },
        prop: "align-items",
        control: "ToggleGroupControl",
        desc: "How items align on the cross axis (perpendicular to flex direction).",
        default: "stretch",
        cssMapping: {
            abbrev: "items",
            classPattern: "flex-items-{value}",
            skipDefault: true
        },
        options: [
            {
                slug: "stretch",
                name: "Stretch",
                niceName: "Stretch",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "center",
                name: "Center",
                niceName: "Center",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "flex-start",
                name: "Flex Start",
                niceName: "Start",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "flex-end",
                name: "Flex End",
                niceName: "End",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "baseline",
                name: "Baseline",
                niceName: "Baseline",
                icon: null,
                availableFor: ["row", "row-reverse"]
            }
        ]
    },
    justifyContent: {
        name: "Justify Content",
        niceName: {
            row: "Horizontal Alignment",
            column: "Vertical Alignment"
        },
        prop: "justify-content",
        control: "ToggleGroupControl",
        desc: "How items align on the main axis (along flex direction).",
        default: "flex-start",
        cssMapping: {
            abbrev: "justify",
            classPattern: "flex-justify-{value}",
            skipDefault: true
        },
        options: [
            {
                slug: "flex-start",
                name: "Flex Start",
                niceName: "Start",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "center",
                name: "Center",
                niceName: "Center",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "flex-end",
                name: "Flex End",
                niceName: "End",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "space-between",
                name: "Space Between",
                niceName: "Space Between",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "space-around",
                name: "Space Around",
                niceName: "Space Around",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "space-evenly",
                name: "Space Evenly",
                niceName: "Space Evenly",
                icon: null,
                availableFor: ["row", "column"]
            }
        ]
    },
    alignContent: {
        name: "Align Content",
        niceName: {
            row: "Line Spacing (Vertical)",
            column: "Line Spacing (Horizontal)"
        },
        prop: "align-content",
        control: "ToggleGroupControl",
        desc: "Controls spacing between wrapped flex lines.",
        default: "stretch",
        cssMapping: {
            abbrev: "content",
            classPattern: "flex-content-{value}",
            skipDefault: true
        },
        showWhen: {
            flexWrap: ["wrap", "wrap-reverse"]
        },
        options: [
            {
                slug: "stretch",
                name: "Stretch",
                niceName: "Stretch",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "center",
                name: "Center",
                niceName: "Center",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "flex-start",
                name: "Flex Start",
                niceName: "Start",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "flex-end",
                name: "Flex End",
                niceName: "End",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "space-between",
                name: "Space Between",
                niceName: "Space Between",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "space-around",
                name: "Space Around",
                niceName: "Space Around",
                icon: null,
                availableFor: ["row", "column"]
            },
            {
                slug: "space-evenly",
                name: "Space Evenly",
                niceName: "Space Evenly",
                icon: null,
                availableFor: ["row", "column"]
            }
        ]
    },
    enableGap: {
        name: "Add Spacing",
        niceName: "Item Spacing",
        prop: "gap",
        control: "ToggleControl",
        desc: "Add space between items in the layout.",
        default: true,
        cssMapping: {
            abbrev: "gap",
            classPattern: "flex-gap",
            skipDefault: false
        }
    },
    restrictContentWidth: {
        name: "Constrain Content",
        niceName: "Constrain Content",
        prop: "max-width",
        control: "ToggleControl",
        desc: "Limit content to the site's standard width.",
        default: false,
        cssMapping: {
            abbrev: "restrict",
            classPattern: "flex-restrict-content",
            skipDefault: false
        },
        showWhen: {
            align: ["full"]
        }
    },
    stackOnMobile: {
        name: "Stack on Mobile",
        niceName: "Stack on Mobile",
        prop: "flex-direction",
        control: "ToggleControl",
        desc: "Stack columns vertically on mobile devices",
        default: true,
        cssMapping: {
            abbrev: "stack",
            classPattern: "flex-stack-mobile",
            skipDefault: false
        },
        responsive: true
    }
};
