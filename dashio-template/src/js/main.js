// =============================================================================
// Dashio - Main JavaScript
// =============================================================================

// Import styles (processed by Vite)
import '../scss/styles.scss'

// Import Bootstrap JS (includes Popper)
import * as bootstrap from 'bootstrap'

// Import charts module
import { initCharts, setupChartThemeObserver } from './charts.js'

// =============================================================================
// DOM Ready Handler
// =============================================================================
document.addEventListener('DOMContentLoaded', () => {
  initTooltips()
  initPopovers()
  initSidebar()
  initThemeToggle()
  initColorSwitcher()
  initCharts()
  setupChartThemeObserver()
  initAddOrderModal()
})

// =============================================================================
// Bootstrap Components Initialization
// =============================================================================

/**
 * Initialize all tooltips
 */
function initTooltips() {
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
  tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el))
}

/**
 * Initialize all popovers
 */
function initPopovers() {
  const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
  popoverTriggerList.forEach(el => new bootstrap.Popover(el))
}

// =============================================================================
// Sidebar
// =============================================================================

/**
 * Initialize sidebar toggle functionality for mobile
 */
function initSidebar() {
  const sidebar = document.querySelector('.sidebar')
  const sidebarToggle = document.querySelector('[data-toggle="sidebar"]')
  const sidebarClose = document.querySelector('[data-dismiss="sidebar"]')
  
  if (!sidebar || !sidebarToggle) return
  
  // Create backdrop element
  let backdrop = document.querySelector('.sidebar-backdrop')
  if (!backdrop) {
    backdrop = document.createElement('div')
    backdrop.className = 'sidebar-backdrop'
    document.body.appendChild(backdrop)
  }
  
  // Toggle sidebar
  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.add('show')
    backdrop.classList.add('show')
    document.body.style.overflow = 'hidden'
  })
  
  // Close sidebar
  const closeSidebar = () => {
    sidebar.classList.remove('show')
    backdrop.classList.remove('show')
    document.body.style.overflow = ''
  }
  
  backdrop.addEventListener('click', closeSidebar)
  sidebarClose?.addEventListener('click', closeSidebar)
  
  // Close on escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('show')) {
      closeSidebar()
    }
  })
}

// =============================================================================
// Theme Toggle (Dark/Light Mode)
// =============================================================================

/**
 * Initialize theme toggle functionality
 */
function initThemeToggle() {
  const themeToggle = document.querySelector('[data-toggle="theme"]')
  if (!themeToggle) return
  
  // Check for saved theme preference or system preference
  const savedTheme = localStorage.getItem('theme')
  const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  const initialTheme = savedTheme || (systemPrefersDark ? 'dark' : 'light')
  
  setTheme(initialTheme)
  updateThemeToggleIcon(themeToggle, initialTheme)
  
  // Toggle theme on click
  themeToggle.addEventListener('click', () => {
    const currentTheme = document.documentElement.getAttribute('data-bs-theme')
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark'
    
    setTheme(newTheme)
    updateThemeToggleIcon(themeToggle, newTheme)
    localStorage.setItem('theme', newTheme)
  })
  
  // Listen for system theme changes
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!localStorage.getItem('theme')) {
      const newTheme = e.matches ? 'dark' : 'light'
      setTheme(newTheme)
      updateThemeToggleIcon(themeToggle, newTheme)
    }
  })
}

/**
 * Set the theme on the document
 */
function setTheme(theme) {
  document.documentElement.setAttribute('data-bs-theme', theme)
}

/**
 * Update the theme toggle button icon
 */
function updateThemeToggleIcon(button, theme) {
  const icon = button.querySelector('i')
  if (!icon) return
  
  icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill'
}

// =============================================================================
// Color Switcher (Demo Mode Feature)
// =============================================================================

// Demo mode: set DASHIO_DEMO=true in .env or define __DASHIO_DEMO__ in vite.config.js
// When disabled, color switcher UI is hidden but saved colors still apply
const DEMO_MODE = typeof __DASHIO_DEMO__ !== 'undefined' ? __DASHIO_DEMO__ : (window.DASHIO_DEMO ?? true)

/**
 * Initialize color switcher functionality
 */
function initColorSwitcher() {
  // Always apply saved color for consistency across pages
  const savedColor = localStorage.getItem('primaryColor')
  if (savedColor) {
    setPrimaryColor(savedColor)
  }

  // Hide color switcher UI if not in demo mode
  const colorSwitcherDropdown = document.querySelector('.color-switcher-dropdown')
  if (colorSwitcherDropdown && !DEMO_MODE) {
    colorSwitcherDropdown.style.display = 'none'
    return
  }

  const colorSwitcher = document.getElementById('colorSwitcher')
  if (!colorSwitcher) return

  // Update active swatch based on saved color
  if (savedColor) {
    updateActiveColorSwatch(colorSwitcher, savedColor)
  }

  // Handle color swatch clicks
  colorSwitcher.addEventListener('click', (e) => {
    const swatch = e.target.closest('.color-swatch')
    if (!swatch) return

    const color = swatch.dataset.color
    setPrimaryColor(color)
    updateActiveColorSwatch(colorSwitcher, color)
    localStorage.setItem('primaryColor', color)
  })
}

/**
 * Set the primary color using CSS custom properties
 */
function setPrimaryColor(hex) {
  const rgb = hexToRgb(hex)
  if (!rgb) return

  const root = document.documentElement

  // Core primary color
  root.style.setProperty('--bs-primary', hex)
  root.style.setProperty('--bs-primary-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`)

  // Darker shade for hover/active states
  const darkerHex = shadeColor(hex, -15)
  root.style.setProperty('--bs-primary-darker', darkerHex)

  // Link colors
  root.style.setProperty('--bs-link-color', hex)
  root.style.setProperty('--bs-link-color-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`)
  root.style.setProperty('--bs-link-hover-color', darkerHex)

  // Re-initialize charts to pick up new color
  setTimeout(() => {
    initCharts()
  }, 50)
}

/**
 * Update the active state on color swatches
 */
function updateActiveColorSwatch(container, activeColor) {
  container.querySelectorAll('.color-swatch').forEach(swatch => {
    swatch.classList.toggle('active', swatch.dataset.color === activeColor)
  })
}

/**
 * Convert hex color to RGB object
 */
function hexToRgb(hex) {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : null
}

/**
 * Shade a hex color (positive = lighter, negative = darker)
 */
function shadeColor(hex, percent) {
  const rgb = hexToRgb(hex)
  if (!rgb) return hex

  const shade = (value) => Math.min(255, Math.max(0, value + Math.round(value * percent / 100)))
  const toHex = (value) => value.toString(16).padStart(2, '0')

  return `#${toHex(shade(rgb.r))}${toHex(shade(rgb.g))}${toHex(shade(rgb.b))}`
}

// =============================================================================
// Add Order Modal (Dashboard)
// =============================================================================

/**
 * Initialize the Add Order modal functionality
 */
function initAddOrderModal() {
  const productPlan = document.getElementById('productPlan')
  const orderAmount = document.getElementById('orderAmount')
  const submitOrder = document.getElementById('submitOrder')
  const addOrderModal = document.getElementById('addOrderModal')
  const addOrderForm = document.getElementById('addOrderForm')

  if (!addOrderModal) return

  // Plan prices
  const planPrices = {
    basic: 99.00,
    premium: 299.00,
    enterprise: 599.00
  }

  // Auto-fill amount when plan is selected
  productPlan?.addEventListener('change', (e) => {
    const price = planPrices[e.target.value]
    if (price && orderAmount) {
      orderAmount.value = price.toFixed(2)
    }
  })

  // Handle form submission
  submitOrder?.addEventListener('click', () => {
    if (!addOrderForm.checkValidity()) {
      addOrderForm.reportValidity()
      return
    }

    // Get form data
    const customerName = document.getElementById('customerName')?.value
    const selectedPlan = productPlan?.options[productPlan.selectedIndex]?.text

    // Close modal
    const modal = bootstrap.Modal.getInstance(addOrderModal)
    modal?.hide()

    // Reset form
    addOrderForm.reset()

    // Show success toast
    showToast(`Order created for ${customerName} - ${selectedPlan}`, 'success')
  })

  // Reset form when modal is closed
  addOrderModal.addEventListener('hidden.bs.modal', () => {
    addOrderForm?.reset()
  })
}

/**
 * Show a toast notification
 */
function showToast(message, type = 'primary') {
  // Create toast container if it doesn't exist
  let toastContainer = document.querySelector('.toast-container')
  if (!toastContainer) {
    toastContainer = document.createElement('div')
    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3'
    toastContainer.style.zIndex = '1100'
    document.body.appendChild(toastContainer)
  }

  // Create toast element
  const toastId = `toast-${Date.now()}`
  const iconMap = {
    success: 'bi-check-circle-fill text-success',
    danger: 'bi-x-circle-fill text-danger',
    warning: 'bi-exclamation-triangle-fill text-warning',
    primary: 'bi-info-circle-fill text-primary'
  }

  const toastHtml = `
    <div id="${toastId}" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi ${iconMap[type] || iconMap.primary}"></i>
          <span>${message}</span>
        </div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  `

  toastContainer.insertAdjacentHTML('beforeend', toastHtml)

  const toastEl = document.getElementById(toastId)
  const toast = new bootstrap.Toast(toastEl, { delay: 4000 })
  toast.show()

  // Remove element after hidden
  toastEl.addEventListener('hidden.bs.toast', () => {
    toastEl.remove()
  })
}

// =============================================================================
// Exports (for use in HTML if needed)
// =============================================================================
window.Dashio = {
  bootstrap,
  setTheme,
  setPrimaryColor,
  initTooltips,
  initPopovers,
  showToast
}
