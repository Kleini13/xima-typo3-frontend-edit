document.addEventListener('DOMContentLoaded', function () {
  // Global state for active element when showElementInfo is enabled
  let activeElement = null;
  let activeWrapper = null;

  /**
  * Finds the closest parent element with an ID matching the pattern "c\d+".
  * @param {HTMLElement} element - The starting element.
  * @returns {HTMLElement|null} - The closest matching element or null if not found.
  */
  const getClosestElementWithId = (element) => {
    while (element && !element.id.match(/c\d+/)) {
      element = element.parentElement;
    }
    return element;
  };

  /**
  * Collects data items from elements with the class "frontend-edit--data".
  * Groups the data by the closest element's ID.
  * @returns {Object} - A dictionary of data items grouped by ID.
  */
  const collectDataItems = () => {
    const dataItems = {};
    document.querySelectorAll('.frontend-edit--data').forEach((element) => {
      const data = element.value;
      const closestElement = getClosestElementWithId(element);

      if (closestElement) {
        const id = closestElement.id.replace('c', '');
        if (!dataItems[id]) {
          dataItems[id] = [];
        }
        dataItems[id].push(JSON.parse(data));
      }
    });
    return dataItems;
  };

  /**
  * Sends a POST request to fetch content elements based on the provided data items.
  * @param {Object} dataItems - The data items to send in the request body.
  * @returns {Promise<Object>} - The JSON response from the server.
  * @throws {Error} - If the request fails.
  */
  const fetchContentElements = async (dataItems) => {
    const url = new URL(window.location.href);
    url.searchParams.set('type', '1729341864');

    try {
      console.log('Frontend Edit: Fetching content elements...', {
        url: url.toString(),
        dataItems: dataItems
      });

      const response = await fetch(url.toString(), {
        cache: 'no-cache',
        method: 'POST',
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify(dataItems),
        credentials: 'same-origin'
      });

      console.log('Frontend Edit: Response received', {
        status: response.status,
        statusText: response.statusText,
        headers: Object.fromEntries(response.headers.entries())
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Frontend Edit: Request failed', {
          status: response.status,
          statusText: response.statusText,
          responseText: errorText
        });
        throw new Error(`Failed to fetch content elements: ${response.status} ${response.statusText}${errorText ? ' - ' + errorText : ''}`);
      }

      const jsonResponse = await response.json();
      console.log('Frontend Edit: JSON response parsed', jsonResponse);
      return jsonResponse;
    } catch (error) {
      console.error('Frontend Edit: Fetch error', error);
      throw error;
    }
  };

  /**
  * Creates an element info display for a content element.
  * @param {string} uid - The unique ID of the content element.
  * @param {Object} elementInfo - The element info data.
  * @returns {HTMLDivElement} - The created element info display.
  */
  const createElementInfo = (uid, elementInfo) => {
    const elementInfoDiv = document.createElement('div');
    elementInfoDiv.className = 'frontend-edit--element-info';
    elementInfoDiv.setAttribute('data-cid', uid);

    const iconDiv = document.createElement('div');
    iconDiv.className = 'frontend-edit--element-info-icon';
    iconDiv.innerHTML = elementInfo.elementIcon;

    const textDiv = document.createElement('div');
    textDiv.className = 'frontend-edit--element-info-text';
    
    const typeSpan = document.createElement('span');
    typeSpan.textContent = elementInfo.elementType;
    
    const nameSpan = document.createElement('span');
    nameSpan.textContent = elementInfo.elementName;
    
    const idCode = document.createElement('code');
    idCode.textContent = `[${elementInfo.elementId}]`;
    
    textDiv.appendChild(typeSpan);
    if (elementInfo.elementName) {
      textDiv.appendChild(nameSpan);
    }
    textDiv.appendChild(idCode);

    elementInfoDiv.appendChild(iconDiv);
    elementInfoDiv.appendChild(textDiv);

    return elementInfoDiv;
  };

  /**
  * Creates an edit button for a content element.
  * @param {string} uid - The unique ID of the content element.
  * @param {Object} contentElement - The content element data.
  * @returns {HTMLButtonElement} - The created edit button.
  */
  const createEditButton = (uid, contentElement) => {
    const editButton = document.createElement('a');
    editButton.className = 'frontend-edit--edit-button';
    editButton.setAttribute('data-cid', uid);
    
    // If showMore is enabled, make this a direct edit link
    if (contentElement.showMore && contentElement.editUrl) {
      editButton.href = contentElement.editUrl;
      editButton.title = 'Edit Content Element';
      editButton.innerHTML = `<svg class="frontend-edit--edit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>`;
    } else {
      // Original behavior for dropdown mode
      if (contentElement.menu.url) {
        editButton.href = contentElement.menu.url;
        if (contentElement.menu.targetBlank) editButton.target = '_blank';
      } else {
        editButton.href = '#';
      }
      editButton.title = contentElement.menu.label;
      editButton.innerHTML = contentElement.menu.icon;
    }
    
    return editButton;
  };

  /**
  * Creates a more button for a content element (when showMore is enabled).
  * @param {string} uid - The unique ID of the content element.
  * @param {Object} contentElement - The content element data.
  * @returns {HTMLButtonElement} - The created more button.
  */
  const createMoreButton = (uid, contentElement) => {
    const moreButton = document.createElement('button');
    moreButton.className = 'frontend-edit--more-button';
    moreButton.title = 'More Options';
    moreButton.setAttribute('data-cid', uid);
    moreButton.innerHTML = `<svg class="frontend-edit--more-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>`;
    
    return moreButton;
  };

  /**
  * Creates a dropdown menu for a content element.
  * @param {string} uid - The unique ID of the content element.
  * @param {Object} contentElement - The content element data.
  * @returns {HTMLDivElement} - The created dropdown menu.
  */
  const createDropdownMenu = (uid, contentElement) => {
    const dropdownMenu = document.createElement('div');
    dropdownMenu.className = 'frontend-edit--dropdown-menu';
    dropdownMenu.setAttribute('data-cid', uid);

    const dropdownMenuInner = document.createElement('div');
    dropdownMenuInner.className = 'frontend-edit--dropdown-menu-inner';
    dropdownMenu.appendChild(dropdownMenuInner);

    for (let actionName in contentElement.menu.children) {
      const action = contentElement.menu.children[actionName];
      const actionElement = document.createElement(action.type === 'link' ? 'a' : 'div');
      if (action.type === 'link') {
        actionElement.href = action.url;
        if (action.targetBlank) actionElement.target = '_blank';
      }
      if (action.type === 'divider') actionElement.className = 'frontend-edit--divider';

      actionElement.classList.add(actionName);
      actionElement.innerHTML = `${action.icon ?? ''} <span>${action.label}</span>`;
      dropdownMenuInner.appendChild(actionElement);
    }

    return dropdownMenu;
  };

  /**
  * Positions the edit button and dropdown menu relative to the target element.
  * @param {HTMLElement} element - The target element.
  * @param {HTMLElement} wrapperElement - The wrapper element containing the button and menu.
  * @param {HTMLElement} editButton - The edit button.
  * @param {HTMLElement} dropdownMenu - The dropdown menu.
  */
  const positionElements = (element, wrapperElement, editButton, dropdownMenu) => {
    const rect = element.getBoundingClientRect();
    const rectInPageContext = {
      top: rect.top + document.documentElement.scrollTop,
      left: rect.left + document.documentElement.scrollLeft,
      width: rect.width,
      height: rect.height,
    };

    let defaultEditButtonMargin = 10;
    // if the element is too small, adjust the position of the edit button
    if (rect.height < 50) {
      defaultEditButtonMargin = (rect.height - 30) / 2;
    }

    // if the dropdown menu is too close to the bottom of the page, move it to the top
    // currently it's not possible to fetch the height of the dropdown menu before it's visible once, so we have to use a fixed value
    if (document.documentElement.scrollHeight - rectInPageContext.top - rect.height < 500 &&
      rect.height < 700 &&
      rectInPageContext.top > 500
    ) {
      dropdownMenu.style.bottom = `19px`;
    } else {
      dropdownMenu.style.top = `${defaultEditButtonMargin + 30}px`;
    }

    wrapperElement.style.top = `${rect.top + document.documentElement.scrollTop}px`;
    wrapperElement.style.left = `${rect.left + document.documentElement.scrollLeft}px`;
    wrapperElement.style.width = `${rect.width}px`;
    editButton.style.display = 'flex';
  };

  /**
  * Deactivates the currently active element (removes buttons and indicators).
  */
  const deactivateCurrentElement = () => {
    if (activeElement && activeWrapper) {
      const buttonGroup = activeWrapper.querySelector('.frontend-edit--button-group');
      const elementInfo = activeWrapper.querySelector('.frontend-edit--element-info');
      
      // Hide buttons
      if (buttonGroup) {
        buttonGroup.style.display = 'none';
      }
      
      // Remove indicator class and hide wrapper only if not hovering
      if (!activeElement.matches(':hover')) {
        activeElement.classList.remove('frontend-edit--indicator');
        activeWrapper.style.display = 'none';
        if (elementInfo) {
          elementInfo.style.display = 'none';
        }
      }
      
      activeElement = null;
      activeWrapper = null;
    }
  };

  /**
  * Activates an element (shows buttons and keeps indicators visible).
  * @param {HTMLElement} element - The element to activate.
  * @param {HTMLElement} wrapperElement - The wrapper element.
  */
  const activateElement = (element, wrapperElement) => {
    // Deactivate current element first
    deactivateCurrentElement();
    
    const buttonGroup = wrapperElement.querySelector('.frontend-edit--button-group');
    const elementInfo = wrapperElement.querySelector('.frontend-edit--element-info');
    
    // Set as active
    activeElement = element;
    activeWrapper = wrapperElement;
    
    // Position and show wrapper
    positionElements(element, wrapperElement, 
      buttonGroup?.querySelector('.frontend-edit--edit-button'),
      buttonGroup?.querySelector('.frontend-edit--dropdown-menu')
    );
    
    // Show wrapper, indicator, elementInfo and buttons
    element.classList.add('frontend-edit--indicator');
    wrapperElement.style.display = 'flex';
    
    if (elementInfo) {
      elementInfo.style.display = 'block';
    }
    if (buttonGroup) {
      buttonGroup.style.display = 'flex';
    }
  };

  /**
  * Sets up hover events for the target element (always shows elementInfo and indicator).
  * @param {HTMLElement} element - The target element.
  * @param {HTMLElement} wrapperElement - The wrapper element containing the button and menu.
  * @param {boolean} hasElementInfo - Whether this element has elementInfo enabled.
  */
  const setupHoverEvents = (element, wrapperElement, hasElementInfo = false) => {
    const elementInfo = wrapperElement.querySelector('.frontend-edit--element-info');
    const buttonGroup = wrapperElement.querySelector('.frontend-edit--button-group');

    element.addEventListener('mouseenter', () => {
      // Always show indicator on hover
      element.classList.add('frontend-edit--indicator');
      
      // Position and show wrapper for elementInfo positioning
      positionElements(element, wrapperElement, 
        buttonGroup?.querySelector('.frontend-edit--edit-button'),
        buttonGroup?.querySelector('.frontend-edit--dropdown-menu')
      );
      
      // Show wrapper so elementInfo can be displayed
      wrapperElement.style.display = 'flex';
      
      // Show elementInfo if it exists
      if (elementInfo) {
        elementInfo.style.display = 'block';
      }
      
      // If elementInfo is NOT enabled, also show buttons on hover (original behavior)
      if (!hasElementInfo && buttonGroup) {
        buttonGroup.style.display = 'flex';
      }
    });

    element.addEventListener('mouseleave', (event) => {
      // For elementInfo mode: only hide if not active
      if (hasElementInfo) {
        if (activeElement !== element) {
          element.classList.remove('frontend-edit--indicator');
          wrapperElement.style.display = 'none';
          if (elementInfo) {
            elementInfo.style.display = 'none';
          }
        }
        // If active, keep everything visible
      } else {
        // Original behavior for non-elementInfo mode
        element.classList.remove('frontend-edit--indicator');
        wrapperElement.style.display = 'none';
        if (elementInfo) {
          elementInfo.style.display = 'none';
        }
        if (buttonGroup) {
          buttonGroup.style.display = 'none';
        }
      }
    });
  };

  /**
  * Sets up click events for elements when elementInfo is enabled.
  * @param {HTMLElement} element - The target element.
  * @param {HTMLElement} wrapperElement - The wrapper element.
  */
  const setupClickEvents = (element, wrapperElement) => {
    element.addEventListener('click', (event) => {
      // Don't activate if clicking on a link or button
      if (event.target.tagName === 'A' || event.target.tagName === 'BUTTON' || 
          event.target.closest('a') || event.target.closest('button')) {
        return;
      }
      
      event.preventDefault();
      event.stopPropagation();
      
      // Only activate if not already active, don't deactivate on same element click
      if (activeElement !== element) {
        activateElement(element, wrapperElement);
      }
      // If clicking on the same element, keep it active (do nothing)
    });
  };

  /**
  * Sets up events for dropdown menus to handle mouse leave and click outside.
  */
  const setupDropdownMenuEvents = () => {
    document.addEventListener('click', (event) => {
      // Handle dropdown menu clicks - only close dropdown, don't hide buttons
      document.querySelectorAll('.frontend-edit--dropdown-menu').forEach((menu) => {
        const editButton = menu.parentElement.querySelector('.frontend-edit--edit-button');
        const moreButton = menu.parentElement.querySelector('.frontend-edit--more-button');
        
        // Close dropdown if clicking outside of it and not on edit/more buttons
        if (!menu.contains(event.target) && 
            !editButton?.contains(event.target) && 
            !moreButton?.contains(event.target)) {
          menu.style.display = 'none';
        }
      });
      
      // Handle active element deactivation when clicking outside
      if (activeElement && activeWrapper) {
        const isClickInsideActiveElement = activeElement.contains(event.target);
        const isClickInsideWrapper = activeWrapper.contains(event.target);
        const isClickOnLink = event.target.tagName === 'A' || event.target.closest('a');
        const isClickOnButton = event.target.tagName === 'BUTTON' || event.target.closest('button');
        
        // Deactivate if clicking outside the element and wrapper, but allow links and buttons to work
        if (!isClickInsideActiveElement && !isClickInsideWrapper) {
          deactivateCurrentElement();
        }
      }
    });
  };

  /**
  * Renders content elements by creating edit buttons and dropdown menus for each.
  * @param {Object} jsonResponse - The JSON response containing content element data.
  */
  const renderContentElements = (jsonResponse) => {
    for (let uid in jsonResponse) {
      const contentElement = jsonResponse[uid];
      let element = document.querySelector(`#c${uid}`);

      if (contentElement.element.l10n_source) {
        element = document.querySelector(`#c${contentElement.element.l10n_source}`);
        if (!element) continue;
        uid = contentElement.element.l10n_source;
      }

      const simpleMode = contentElement.menu.url;
      const showMore = contentElement.showMore;
      const editButton = createEditButton(uid, contentElement);

      const dropdownMenu = createDropdownMenu(uid, contentElement);
      let moreButton = null;

      // Create more button if showMore is enabled
      if (showMore) {
        moreButton = createMoreButton(uid, contentElement);
        
        // More button opens dropdown
        moreButton.addEventListener('click', (event) => {
          event.preventDefault();
          dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });
      } else {
        // Original edit button behavior for dropdown mode
        editButton.addEventListener('click', (event) => {
          if (!simpleMode) {
            event.preventDefault();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'visible' : 'block';
          }
        });
      }

      // Add CType-specific class to the element
      if (contentElement.element && contentElement.element.CType) {
        element.classList.add(`frontend-edit--indicator-${contentElement.element.CType}`);
      }

      const wrapperElement = document.createElement('div');
      wrapperElement.className = `frontend-edit--wrapper frontend-edit--wrapper-${contentElement.element.CType}`;
      
      // Create element info if available
      if (contentElement.elementInfo) {
        const elementInfo = createElementInfo(uid, contentElement.elementInfo);
        wrapperElement.appendChild(elementInfo);
      }
      
      // Create button group for buttons
      const buttonGroup = document.createElement('div');
      buttonGroup.className = 'frontend-edit--button-group';
      buttonGroup.appendChild(editButton);
      
      // Add more button if showMore is enabled
      if (showMore && moreButton) {
        buttonGroup.appendChild(moreButton);
      }
      
      // Add dropdown menu
      if (!simpleMode) {
        buttonGroup.appendChild(dropdownMenu);
      }
      
      wrapperElement.appendChild(buttonGroup);
      document.body.appendChild(wrapperElement);

      const hasElementInfo = !!contentElement.elementInfo;
      
      // Setup events based on whether elementInfo is enabled
      setupHoverEvents(element, wrapperElement, hasElementInfo);
      
      // If elementInfo is enabled, also setup click events for button activation
      if (hasElementInfo) {
        setupClickEvents(element, wrapperElement);
      }
    }
  };

  /**
  * Main function to collect data, fetch content elements, and render them.
  * Handles errors during the process.
  */
  const getContentElements = async () => {
    try {
      const dataItems = collectDataItems();
      const jsonResponse = await fetchContentElements(dataItems);
      renderContentElements(jsonResponse);
      setupDropdownMenuEvents();
    } catch (error) {
      console.error(error);
    }
  };

  getContentElements();
});
