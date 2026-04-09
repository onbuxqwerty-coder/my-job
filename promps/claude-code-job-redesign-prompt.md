# Job Search Website Redesign - Claude Code Precise Prompt

## CRITICAL INSTRUCTIONS FOR CLAUDE CODE

**IMPORTANT:** This is a step-by-step redesign prompt. Follow each step in order. Do NOT modify the HTML structure unless explicitly told. Focus on CSS changes first, then minimal HTML modifications only where absolutely necessary.

**Before starting:** Show me the current file structure and list all HTML and CSS files involved.

---

## STEP 1: BACKUP & PREPARATION

1. Create a backup of all current files
2. List all CSS files that will be modified
3. Do NOT delete any existing code - we will refactor it

---

## STEP 2: ADD REQUIRED CSS VARIABLES

At the top of your main CSS file, add this color palette as CSS custom properties:

```css
:root {
  /* Primary Colors */
  --color-primary-blue: #3B82F6;
  --color-accent-orange: #F59E0B;
  --color-success-green: #10B981;
  --color-warning-purple: #8B5CF6;
  --color-danger-pink: #EC4899;
  
  /* Text Colors */
  --color-text-dark: #1F2937;
  --color-text-gray: #6B7280;
  --color-text-light-gray: #9CA3AF;
  
  /* Background Colors */
  --color-bg-white: #FFFFFF;
  --color-bg-light: #F9FAFB;
  --color-bg-gray: #F3F4F6;
  --color-bg-dark: #1F2937;
  
  /* Border Colors */
  --color-border: #E5E7EB;
  --color-border-dark: #D1D5DB;
  
  /* Spacing */
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 12px;
  --spacing-lg: 16px;
  --spacing-xl: 20px;
  --spacing-2xl: 24px;
  --spacing-3xl: 32px;
  --spacing-4xl: 40px;
  
  /* Border Radius */
  --radius-sm: 4px;
  --radius-md: 6px;
  --radius-lg: 8px;
  --radius-xl: 12px;
  
  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 2px 8px 0 rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 8px 16px 0 rgba(0, 0, 0, 0.15);
  --shadow-hover: 0 10px 24px 0 rgba(0, 0, 0, 0.15);
  
  /* Transitions */
  --transition-fast: 0.2s ease-in-out;
  --transition-normal: 0.3s ease-in-out;
  --transition-slow: 0.5s ease-in-out;
}
```

---

## STEP 3: RESET & BASE STYLES

Apply these base styles (replace existing if similar):

```css
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  background-color: var(--color-bg-light);
  color: var(--color-text-dark);
  line-height: 1.6;
}

html, body {
  width: 100%;
  height: 100%;
}
```

---

## STEP 4: HEADER STYLES

Replace all header styling with this:

```css
/* Header Container */
header {
  background-color: var(--color-bg-white);
  border-bottom: 1px solid var(--color-border);
  padding: var(--spacing-xl) var(--spacing-lg);
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: var(--shadow-sm);
}

.header-wrapper {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

/* Logo Section */
.logo-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.header-title {
  flex: 1;
  text-align: center;
}

.header-title h1 {
  font-size: 24px;
  font-weight: bold;
  color: var(--color-text-dark);
  margin-bottom: var(--spacing-xs);
}

.header-subtitle {
  font-size: 14px;
  color: var(--color-text-gray);
}

.hamburger-btn {
  width: 44px;
  height: 44px;
  background-color: var(--color-primary-blue);
  border: none;
  border-radius: var(--radius-md);
  color: white;
  font-size: 20px;
  cursor: pointer;
  display: none;
  flex-shrink: 0;
  transition: all var(--transition-fast);
}

.hamburger-btn:hover {
  background-color: #2563EB;
  box-shadow: var(--shadow-md);
}

/* Search Bar */
.search-container {
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
}

.search-input {
  width: 100%;
  height: 48px;
  padding: var(--spacing-md) var(--spacing-lg);
  font-size: 16px;
  border: 2px solid var(--color-border);
  border-radius: var(--radius-lg);
  transition: all var(--transition-fast);
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary-blue);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-input::placeholder {
  color: var(--color-text-light-gray);
}

/* Mobile Header */
@media (max-width: 767px) {
  header {
    padding: var(--spacing-lg);
  }

  .header-title h1 {
    font-size: 18px;
  }

  .hamburger-btn {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .search-container {
    max-width: 100%;
  }
}
```

---

## STEP 5: MAIN CONTAINER & LAYOUT

```css
/* Main Container */
main {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-3xl) var(--spacing-lg);
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: var(--spacing-2xl);
}

/* Desktop Layout */
@media (min-width: 1024px) {
  main {
    grid-template-columns: 280px 1fr;
  }
}

/* Tablet Layout */
@media (768px - 1023px) {
  main {
    grid-template-columns: 1fr;
    padding: var(--spacing-2xl) var(--spacing-lg);
  }
}

/* Mobile Layout */
@media (max-width: 767px) {
  main {
    grid-template-columns: 1fr;
    padding: var(--spacing-lg);
  }
}
```

---

## STEP 6: FILTERS SIDEBAR STYLES

```css
/* Filters Sidebar */
aside.filters {
  background-color: var(--color-bg-gray);
  padding: var(--spacing-xl);
  border-radius: var(--radius-lg);
  height: fit-content;
  position: sticky;
  top: 120px;
}

.filter-section {
  padding-bottom: var(--spacing-xl);
  border-bottom: 1px solid var(--color-border);
}

.filter-section:last-child {
  border-bottom: none;
}

.filter-label {
  font-size: 12px;
  font-weight: bold;
  color: var(--color-text-dark);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: var(--spacing-md);
  display: block;
}

/* Select Dropdowns */
select {
  width: 100%;
  min-height: 44px;
  padding: var(--spacing-md) var(--spacing-lg);
  font-size: 16px;
  background-color: var(--color-bg-white);
  border: 1px solid var(--color-border-dark);
  border-radius: var(--radius-md);
  color: var(--color-text-dark);
  cursor: pointer;
  transition: all var(--transition-fast);
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right var(--spacing-lg) center;
  padding-right: var(--spacing-3xl);
}

select:focus {
  outline: none;
  border-color: var(--color-primary-blue);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

select:hover {
  background-color: var(--color-bg-light);
}

/* Checkboxes */
input[type="checkbox"] {
  width: 20px;
  height: 20px;
  margin-right: var(--spacing-md);
  cursor: pointer;
  accent-color: var(--color-primary-blue);
}

.checkbox-group {
  margin-top: var(--spacing-md);
}

.checkbox-item {
  display: flex;
  align-items: center;
  margin-bottom: var(--spacing-md);
  font-size: 16px;
}

/* Mobile Filters Hidden */
@media (max-width: 1023px) {
  aside.filters {
    display: none;
  }
}
```

---

## STEP 7: JOB LISTINGS CONTAINER

```css
/* Jobs Container */
.jobs-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.jobs-header {
  font-size: 14px;
  color: var(--color-text-gray);
  padding-bottom: var(--spacing-lg);
  border-bottom: 1px solid var(--color-border);
}
```

---

## STEP 8: JOB CARD STYLES (MOST IMPORTANT)

Replace ALL existing job card styling with this exact structure:

```css
/* Job Card Container */
.job-card {
  background-color: var(--color-bg-white);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  display: grid;
  grid-template-columns: 1fr auto;
  gap: var(--spacing-xl);
  align-items: start;
  cursor: pointer;
  transition: all var(--transition-normal);
  box-shadow: var(--shadow-sm);
}

.job-card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-4px);
  border-color: var(--color-primary-blue);
}

/* Left side - job info */
.job-info {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

/* Job Title */
.job-title {
  font-size: 20px;
  font-weight: bold;
  color: var(--color-text-dark);
  line-height: 1.4;
  margin: 0;
}

/* Job Details Row 1: Company & Location */
.job-details-row {
  display: flex;
  gap: var(--spacing-lg);
  flex-wrap: wrap;
  align-items: center;
  font-size: 14px;
}

.job-company {
  color: var(--color-primary-blue);
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: color var(--transition-fast);
}

.job-company:hover {
  color: #2563EB;
  text-decoration: underline;
}

.job-location {
  color: var(--color-text-gray);
}

/* Job Details Row 2: Contract type badges */
.job-badges {
  display: flex;
  gap: var(--spacing-md);
  flex-wrap: wrap;
}

.badge {
  display: inline-block;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 500;
  color: white;
  border-radius: 999px;
  white-space: nowrap;
}

.badge--remote {
  background-color: var(--color-primary-blue);
}

.badge--hybrid {
  background-color: var(--color-accent-orange);
}

.badge--full-time {
  background-color: var(--color-success-green);
}

.badge--contract {
  background-color: var(--color-warning-purple);
}

.badge--part-time {
  background-color: var(--color-danger-pink);
}

/* Right side - salary & date */
.job-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: var(--spacing-sm);
  text-align: right;
}

.job-salary {
  font-size: 18px;
  font-weight: bold;
  color: var(--color-text-dark);
}

.job-posted {
  font-size: 14px;
  color: var(--color-text-gray);
}

/* Mobile Job Card */
@media (max-width: 767px) {
  .job-card {
    grid-template-columns: 1fr;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
  }

  .job-title {
    font-size: 16px;
  }

  .job-meta {
    align-items: flex-start;
    text-align: left;
  }

  .job-card:hover {
    transform: none;
    background-color: var(--color-bg-light);
  }
}
```

---

## STEP 9: PAGINATION STYLES

```css
/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: var(--spacing-md);
  margin-top: var(--spacing-3xl);
  padding-top: var(--spacing-2xl);
  border-top: 1px solid var(--color-border);
}

.pagination-info {
  font-size: 14px;
  color: var(--color-text-gray);
  margin-right: var(--spacing-md);
}

.pagination-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid var(--color-border-dark);
  background-color: var(--color-bg-white);
  border-radius: var(--radius-md);
  color: var(--color-text-dark);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.pagination-btn:hover {
  background-color: var(--color-bg-gray);
  border-color: var(--color-primary-blue);
  color: var(--color-primary-blue);
}

.pagination-btn.active {
  background-color: var(--color-primary-blue);
  color: white;
  border-color: var(--color-primary-blue);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 767px) {
  .pagination-info {
    font-size: 12px;
  }
}
```

---

## STEP 10: FOOTER STYLES

```css
/* Footer */
footer {
  background-color: var(--color-bg-dark);
  color: #D1D5DB;
  padding: var(--spacing-4xl) var(--spacing-lg);
  margin-top: var(--spacing-4xl);
  text-align: center;
}

.footer-content {
  max-width: 1200px;
  margin: 0 auto;
}

.footer-logo {
  width: 48px;
  height: 48px;
  margin: 0 auto var(--spacing-lg);
}

.footer-logo img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.footer-company-name {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: var(--spacing-md);
  color: white;
}

.footer-description {
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: var(--spacing-2xl);
}

.footer-links {
  font-size: 14px;
  margin-bottom: var(--spacing-2xl);
}

.footer-links a {
  color: #D1D5DB;
  text-decoration: none;
  transition: color var(--transition-fast);
}

.footer-links a:hover {
  color: white;
}

.footer-links a:not(:last-child)::after {
  content: " • ";
  margin: 0 var(--spacing-md);
}

.footer-copyright {
  font-size: 12px;
  color: #6B7280;
  border-top: 1px solid #374151;
  padding-top: var(--spacing-lg);
}

@media (max-width: 767px) {
  footer {
    padding: var(--spacing-2xl) var(--spacing-lg);
  }

  .footer-company-name {
    font-size: 14px;
  }
}
```

---

## STEP 11: MOBILE FILTERS MODAL (NEW)

This requires a small HTML modification. Add this CSS:

```css
/* Mobile Filters Modal */
.filters-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 200;
  overflow-y: auto;
}

.filters-modal.active {
  display: flex;
  flex-direction: column;
}

.filters-modal-content {
  background-color: var(--color-bg-white);
  width: 90vw;
  max-width: 400px;
  margin-left: auto;
  margin-top: auto;
  border-radius: var(--radius-xl) var(--radius-xl) 0 0;
  padding: var(--spacing-xl);
  animation: slideUp var(--transition-normal);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.filters-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: var(--spacing-lg);
}

.filters-modal-title {
  font-size: 18px;
  font-weight: bold;
  color: var(--color-text-dark);
}

.filters-modal-close {
  width: 32px;
  height: 32px;
  background: none;
  border: none;
  font-size: 24px;
  color: var(--color-text-gray);
  cursor: pointer;
  transition: color var(--transition-fast);
}

.filters-modal-close:hover {
  color: var(--color-text-dark);
}

.filters-modal-footer {
  display: flex;
  gap: var(--spacing-lg);
  border-top: 1px solid var(--color-border);
  padding-top: var(--spacing-xl);
}

.filters-modal-apply {
  flex: 1;
  height: 44px;
  background-color: var(--color-primary-blue);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.filters-modal-apply:hover {
  background-color: #2563EB;
}

/* Show filters as modal on mobile */
@media (max-width: 1023px) {
  aside.filters {
    display: none !important;
  }

  .filters-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }

  .filters-modal-content {
    width: 100%;
    max-width: 100%;
    margin: auto 0 0 0;
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
  }
}
```

---

## STEP 12: MINIMAL HTML STRUCTURE CHANGES

Make sure your HTML has this structure (minimal changes, mostly wrapping):

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyJob - Find Your Next Job</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <!-- HEADER -->
  <header>
    <div class="header-wrapper">
      <div class="logo-section">
        <div class="logo">
          <img src="logo.png" alt="MyJob Logo">
        </div>
        <div class="header-title">
          <h1>Find Your Next Job</h1>
          <p class="header-subtitle">50,000+ active job openings</p>
        </div>
        <button class="hamburger-btn">☰</button>
      </div>
      <div class="search-container">
        <input 
          type="text" 
          class="search-input" 
          placeholder="Job title, company, keywords..."
        >
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT -->
  <main>
    <!-- FILTERS SIDEBAR (Desktop) -->
    <aside class="filters">
      <!-- Your existing filter HTML, will be styled by new CSS -->
    </aside>

    <!-- JOB LISTINGS -->
    <section>
      <div class="jobs-header">Showing 1 to 10 of 50 results</div>
      
      <div class="jobs-container">
        <!-- Each job should follow this structure: -->
        <div class="job-card">
          <div class="job-info">
            <h2 class="job-title">Job Title Here</h2>
            
            <div class="job-details-row">
              <a href="#" class="job-company">Company Name</a>
              <span class="job-location">City, Country</span>
            </div>
            
            <div class="job-badges">
              <span class="badge badge--remote">REMOTE</span>
            </div>
          </div>
          
          <div class="job-meta">
            <div class="job-salary">$50,000 - $70,000</div>
            <div class="job-posted">2 days ago</div>
          </div>
        </div>
        <!-- Repeat for each job -->
      </div>

      <!-- PAGINATION -->
      <div class="pagination">
        <span class="pagination-info">Showing 1 to 10 of 50 results</span>
        <button class="pagination-btn">←</button>
        <button class="pagination-btn active">1</button>
        <button class="pagination-btn">2</button>
        <button class="pagination-btn">3</button>
        <button class="pagination-btn">4</button>
        <button class="pagination-btn">5</button>
        <button class="pagination-btn">→</button>
      </div>
    </section>
  </main>

  <!-- FOOTER -->
  <footer>
    <div class="footer-content">
      <div class="footer-logo">
        <img src="logo.png" alt="MyJob Logo">
      </div>
      <h2 class="footer-company-name">MyJob</h2>
      <p class="footer-description">Ukraine's leading job search platform. Find your perfect job today.</p>
      
      <div class="footer-links">
        <a href="#">Browse Jobs</a>
        <a href="#">Categories</a>
        <a href="#">About Us</a>
        <a href="#">Contact</a>
      </div>
      
      <div class="footer-copyright">
        © 2026 MyJob. All rights reserved.
      </div>
    </div>
  </footer>

  <!-- FILTERS MODAL (Mobile) -->
  <div class="filters-modal" id="filtersModal">
    <div class="filters-modal-content">
      <div class="filters-modal-header">
        <h2 class="filters-modal-title">Filters</h2>
        <button class="filters-modal-close">✕</button>
      </div>
      
      <!-- Copy all filter sections here -->
      
      <div class="filters-modal-footer">
        <button class="filters-modal-apply">Apply Filters</button>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
</body>
</html>
```

---

## STEP 13: BASIC JAVASCRIPT FOR MOBILE FILTERS

Add this to your `script.js`:

```javascript
// Mobile Filters Modal
const hamburgerBtn = document.querySelector('.hamburger-btn');
const filtersModal = document.getElementById('filtersModal');
const filtersModalClose = document.querySelector('.filters-modal-close');
const filtersModalApply = document.querySelector('.filters-modal-apply');

// Open modal
hamburgerBtn?.addEventListener('click', () => {
  filtersModal.classList.add('active');
});

// Close modal
filtersModalClose?.addEventListener('click', () => {
  filtersModal.classList.remove('active');
});

// Apply filters (close modal)
filtersModalApply?.addEventListener('click', () => {
  filtersModal.classList.remove('active');
});

// Close modal when clicking outside
filtersModal?.addEventListener('click', (e) => {
  if (e.target === filtersModal) {
    filtersModal.classList.remove('active');
  }
});
```

---

## STEP 14: VERIFICATION CHECKLIST

After implementing, verify:

- [ ] Header is sticky and visible on scroll
- [ ] Search bar is responsive
- [ ] Job cards have white background with shadow
- [ ] Job cards lift on hover
- [ ] Badges show with correct colors
- [ ] Salary is right-aligned
- [ ] Filters sidebar visible on desktop
- [ ] Hamburger button visible on mobile
- [ ] Filters modal opens/closes on mobile
- [ ] Pagination buttons work
- [ ] Footer is dark with correct styling
- [ ] All colors use CSS variables
- [ ] Mobile layout is single column
- [ ] Tablet layout is responsive

---

## IMPLEMENTATION ORDER

1. **Start with STEP 2** - Add CSS variables
2. **Then STEP 3** - Add base styles
3. **Then STEP 4-10** - Add all CSS sections
4. **Then STEP 11-12** - Add HTML structure (minimal changes)
5. **Then STEP 13** - Add JavaScript
6. **Finally STEP 14** - Test everything

---

## TROUBLESHOOTING

If Claude Code has issues:

1. **If cards still look wrong**: Check that old CSS isn't conflicting. Use `!important` if necessary.
2. **If layout breaks**: Make sure max-width 1200px is set on main container.
3. **If colors don't apply**: Verify CSS variables are defined in `:root`
4. **If mobile doesn't work**: Check media queries are correct and hamburger button is visible.

---

## FINAL NOTES

This prompt is designed to be followed **step-by-step**. Don't skip steps. Each step builds on previous ones.

If something doesn't work as expected, Claude Code should:
1. Show you what it changed
2. Ask you to verify if it matches this spec
3. Make corrections if needed

Good luck! This should produce a professional, modern job search site. 🚀
