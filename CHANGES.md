# Changelog - TYPO3 Frontend Edit Extension

## New Features

### 1. Element Info Display Feature (`showElementInfo`)

**Purpose**: Extract element information from the dropdown menu and display it as a separate visual element in the frontend.

#### Files Modified:
- `Configuration/TypoScript/constants.typoscript`: Added `showElementInfo = 1` constant
- `Configuration/TypoScript/setup.typoscript`: Added TypoScript setup for the new constant
- `Classes/Service/SettingsService.php`: Added `getShowElementInfo()` method
- `Classes/Service/MenuGenerator.php`: 
  - Added `getElementInfo()` method to generate element info data
  - Modified `getDropdown()` to conditionally exclude `div_info` section when `showElementInfo` is enabled
  - Added `elementInfo` data to JSON response
- `Resources/Public/JavaScript/frontend_edit.js`:
  - Added `createElementInfo()` function to generate HTML element info display
  - Modified `renderContentElements()` to render element info when available
  - Updated hover events to show/hide element info

#### Functionality:
When enabled, displays element type, name, and ID as a separate info box in the frontend, while removing the info section from the dropdown menu.

**Enhanced Interaction Behavior**: When `showElementInfo` is active, the interaction behavior changes:
- **Hover**: Shows element info and outline indicator
- **Click**: Activates edit/more buttons persistently until clicking elsewhere or on another element
- **Links within elements**: Continue to function normally without interfering with the activation system

---

### 2. Separate Edit and More Buttons Feature (`showMore`)

**Purpose**: Split the edit functionality into two separate buttons - a direct edit button and a "more options" button for the dropdown menu.

#### Files Modified:
- `Configuration/TypoScript/constants.typoscript`: Added `showMore = 0` constant
- `Configuration/TypoScript/setup.typoscript`: Added TypoScript setup for the new constant
- `Classes/Service/SettingsService.php`: Added `getShowMore()` method
- `Classes/Service/MenuGenerator.php`:
  - Added `showMore` variable to `getDropdown()` method
  - Extended data structure to include `showMore` flag and `editUrl` when enabled
- `Resources/Public/JavaScript/frontend_edit.js`:
  - Modified `createEditButton()` to create direct edit links when `showMore` is enabled
  - Added `createMoreButton()` function to create the "more options" button
  - Updated `renderContentElements()` to handle both button modes
  - Modified event handling: edit button becomes direct link, more button opens dropdown
  - Updated hover events to handle both buttons

#### Functionality:
When enabled, the edit button becomes a direct link to the edit form, and a separate "more" button (three dots icon) is added to access the dropdown menu with additional options.

---

### 3. Enhanced Click-Based Interaction (`showElementInfo` Enhancement)

**Purpose**: Improve the user experience when `showElementInfo` is enabled by implementing a two-stage interaction system.

#### Files Modified:
- `Resources/Public/JavaScript/frontend_edit.js`:
  - Added global state management for active elements (`activeElement`, `activeWrapper`)
  - Added `deactivateCurrentElement()` function to handle element deactivation
  - Added `activateElement()` function to handle element activation
  - Modified `setupHoverEvents()` to distinguish between elementInfo and normal modes
  - Added `setupClickEvents()` function for click-based button activation
  - Enhanced document click handler to deactivate elements when clicking outside
  - Improved link and button handling to preserve normal functionality
  - Implemented wrapper-based visibility control for consistent elementInfo display

#### Functionality:
**Two-Stage Interaction System**:
1. **Stage 1 (Hover)**: Element info and outline indicator are shown on hover
2. **Stage 2 (Click)**: Edit/More buttons become visible and remain persistent until deactivated

**Interaction Behavior**:
- **Without `showElementInfo`**: Original hover-based behavior for all elements
- **With `showElementInfo`**: 
  - Hover shows element info and outline
  - Click activates buttons persistently
  - Click outside or on another element deactivates current element
  - Links and buttons within elements continue to work normally

**Wrapper-Based Display Logic**:
- Wrapper element is shown/hidden to control elementInfo positioning
- Ensures consistent and reliable elementInfo display
- Proper positioning through JavaScript-controlled wrapper visibility

---

### 4. CType-Specific CSS Classes

**Purpose**: Add CType-specific CSS classes to wrapper and indicator elements for enhanced styling capabilities and improved CSS selector compatibility.

#### Files Modified:
- `Resources/Public/JavaScript/frontend_edit.js`:
  - Added CType-specific class to indicator elements: `frontend-edit--indicator-${contentElement.element.CType}`
  - Added CType-specific class to wrapper elements: `frontend-edit--wrapper-${contentElement.element.CType}`
  - Updated CSS class from `frontend-edit--edit-container` to `frontend-edit--indicator` for compatibility

#### Implementation Details:
```javascript
// Add CType-specific class to the element
if (contentElement.element && contentElement.element.CType) {
  element.classList.add(`frontend-edit--indicator-${contentElement.element.CType}`);
}

// Create wrapper with CType-specific class
const wrapperElement = document.createElement('div');
wrapperElement.className = `frontend-edit--wrapper frontend-edit--wrapper-${contentElement.element.CType}`;
```

#### Functionality:
- **Enhanced Styling**: Allows for CType-specific styling through CSS selectors
- **Better Targeting**: Enables precise styling for different content element types
- **Improved Compatibility**: CSS class name standardization ensures consistent selector functionality

#### Benefits:
- **Flexible Styling**: Target specific content types (e.g., `.frontend-edit--indicator-textmedia`, `.frontend-edit--wrapper-header`)
- **Backward Compatibility**: Maintains existing functionality while adding new capabilities
- **Consistent Naming**: Standardized CSS class naming convention across the extension

---


## Technical Implementation Details

### Button Grouping
- All buttons are consistently wrapped in `.frontend-edit--button-group` for uniform positioning
- CSS classes `.frontend-edit--edit-button` and `.frontend-edit--more-button` utilize existing styles

### Wrapper-Based Display Control
- Element wrapper visibility is controlled via JavaScript for proper positioning
- Ensures elementInfo can be displayed with correct positioning
- Resolves issues with CSS-only solutions that couldn't handle positioning requirements

### Backward Compatibility
- All features are disabled by default (`showElementInfo = 1`, `showMore = 0`)
- Existing functionality remains unchanged when features are disabled
- No breaking changes to existing API or CSS classes
- Original icon system preserved for non-showMore modes

### Configuration
All features can be controlled via TypoScript constants:

```typoscript
plugin.tx_ximatypo3frontendedit.settings {
    showElementInfo = 1  # Enable separate element info display
    showMore = 0         # Enable separate edit and more buttons
}
```

## Benefits
1. **Improved UX**: Direct edit access reduces clicks for common editing tasks
2. **Better Visual Hierarchy**: Element info is more prominent when displayed separately
3. **Flexible Configuration**: Features can be enabled/disabled independently
4. **Reliable Display**: Wrapper-based control ensures consistent elementInfo visibility

## Migration Notes
- No migration required - features are opt-in via TypoScript configuration
- Existing CSS classes and JavaScript functionality remain unchanged
- All features can be enabled independently or together
- CSS-based icons are automatically used when showMore is enabled
