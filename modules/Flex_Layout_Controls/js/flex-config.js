/**
 * Flex Layout Controls - Configuration
 *
 * Centralized configuration object - source of truth for all flex controls
 */

window.FlexControlsConfig = {
    flexDirection: {
        name: "Flex Direction",
        niceName: "Orientation",
        prop: "flex-direction",
        control: "ToggleGroupControl",
        desc: "Direction of flow for content.",
        default: "row",
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
    }
};
