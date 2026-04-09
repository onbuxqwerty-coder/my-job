# Job Search Website - Complete Design Redesign Prompt

## Overview
Redesign the job search website with modern, clean UI/UX. Focus on visual hierarchy, readability, and mobile responsiveness.

---

## Job Listing Cards

### Card Structure
- White cards with shadow: `box-shadow: 0 2px 8px rgba(0,0,0,0.1)`
- Padding: `20px` inside each card
- Margin between cards: `16px`
- Border radius: `8px`
- Hover effect: Card lifts up slightly with increased shadow

### Card Layout (Left to Right)
```
[Job Title - 20px bold black]
[Company Name - 16px blue #3B82F6, clickable link]
[City - 14px gray #6B7280]
[Contract Type Badge] [Salary - 18px bold black] [Posted Date - 14px gray]
```

### Job Title
- Font size: `20px`
- Font weight: `bold`
- Color: `#1F2937` (dark gray/black)
- Line height: `1.4`

### Company Name
- Font size: `16px`
- Color: `#3B82F6` (blue)
- Clickable link (cursor pointer)
- No text decoration by default, underline on hover

### Location
- Font size: `14px`
- Color: `#6B7280` (gray)
- Display below company name

### Contract Type Badges
Display as small colored pills with rounded corners:
- **REMOTE**: Background `#3B82F6` (blue), white text, padding `6px 12px`, font-size `12px`
- **HYBRID**: Background `#F59E0B` (orange), white text, padding `6px 12px`, font-size `12px`
- **FULL TIME**: Background `#10B981` (green), white text, padding `6px 12px`, font-size `12px`
- **CONTRACT**: Background `#8B5CF6` (purple), white text, padding `6px 12px`, font-size `12px`
- **PART TIME**: Background `#EC4899` (pink), white text, padding `6px 12px`, font-size `12px`

### Salary & Date
- Salary: Right-aligned, `18px`, bold, `#1F2937`
- Posted Date: Right side, `14px`, gray `#6B7280`, smaller text

---

## Grid Layout

### Desktop (1024px and above)
- Main content container: max-width `1200px`
- Two-column layout:
  - Left sidebar: `280px` (filters)
  - Right main content: remaining space
- Gap between columns: `24px`

### Tablet (768px - 1023px)
- Single column layout
- Filters above job listings (collapsible section)
- Job cards: full width

### Mobile (below 768px)
- Single column layout
- Filters: hidden by default, accessible via "Filters" button
- Filters open in modal overlay when button clicked
- Job cards: full width with `16px` margin on sides

---

## Filter Sidebar (Desktop)

### Sidebar Container
- Background: `#F3F4F6` (light gray)
- Padding: `20px`
- Border radius: `8px`
- Position: sticky (stays in view while scrolling)

### Filter Sections
- Each filter in its own container
- Border-bottom: `1px solid #E5E7EB` between sections
- Padding-bottom: `20px` for each section

### Filter Labels
- Font size: `14px`
- Font weight: `bold`
- Color: `#1F2937`
- Margin-bottom: `12px`
- Text transform: `uppercase`
- Letter spacing: `0.5px`

### Checkboxes & Select Dropdowns
- Font size: `16px`
- Padding: `8px 12px`
- Border: `1px solid #D1D5DB`
- Border radius: `6px`
- Focus state: outline `2px solid #3B82F6`
- Hover state: background `#F9FAFB`

### Select Dropdown Styling
- Width: `100%`
- Min-height: `44px`
- Font size: `16px`
- Padding: `12px`
- Background: white
- Border: `1px solid #D1D5DB`
- Border radius: `6px`

---

## Header Section

### Header Container
- Background: white
- Border-bottom: `1px solid #E5E7EB`
- Padding: `20px` (desktop), `16px` (mobile)
- Position: sticky, z-index high enough to stay above content

### Logo
- Size: `48px x 48px`
- Centered in header
- Margin-right: `auto` (if left-aligned)

### Main Title
- Font size: `24px` (desktop), `18px` (mobile)
- Font weight: `bold`
- Text: "Find Your Next Job"
- Color: `#1F2937`
- Centered or below logo

### Subtitle
- Font size: `16px`
- Color: `#6B7280` (gray)
- Text: "50,000+ active job openings"
- Centered, below title

### Search Bar
- Width: `100%` (constrained to max-width container)
- Height: `48px`
- Font size: `16px`
- Padding: `12px 16px`
- Border: `2px solid #E5E7EB`
- Border radius: `8px`
- Focus state: border-color `#3B82F6`, box-shadow `0 0 0 3px rgba(59, 130, 246, 0.1)`
- Placeholder color: `#9CA3AF`
- Margin-top: `12px`
- Below title/subtitle

---

## Color Palette

```
Primary Blue:       #3B82F6
Orange Accent:      #F59E0B
Green (Full Time):  #10B981
Purple (Contract):  #8B5CF6
Pink (Part Time):   #EC4899

Text - Dark:        #1F2937
Text - Gray:        #6B7280
Text - Light Gray:  #9CA3AF

Background - White: #FFFFFF
Background - Light: #F9FAFB
Background - Gray:  #F3F4F6
Border Color:       #E5E7EB
```

---

## Mobile Filters Modal

### Filter Button
- Position: top-right of page (desktop hidden)
- Size: `44px x 44px`
- Icon: hamburger menu (three horizontal lines)
- Background: `#3B82F6` (blue)
- Color: white
- Border radius: `8px`
- Font size: `20px`

### Modal Overlay
- Background: `rgba(0, 0, 0, 0.5)` (semi-transparent black)
- Full screen overlay
- Closes when clicking outside or on close button

### Modal Content
- Background: white
- Width: `90vw` (90% of viewport)
- Max-width: `400px`
- Position: from right side, slide-in animation
- Padding: `20px`
- Border radius: `12px` (top corners)

### Modal Header
- Title: "Filters"
- Font size: `18px`
- Font weight: `bold`
- Close button (X icon) in top-right
- Border-bottom: `1px solid #E5E7EB`
- Padding-bottom: `16px`

### Modal Footer
- "Apply Filters" button
- Width: `100%`
- Height: `44px`
- Background: `#3B82F6` (blue)
- Color: white
- Border radius: `6px`
- Font size: `16px`
- Font weight: `bold`
- Margin-top: `20px`

---

## Job Card Hover Effects

### Desktop Hover State
- Box shadow increases: `0 8px 16px rgba(0,0,0,0.15)`
- Transform: `translateY(-4px)` (lifts card up)
- Transition: `all 0.3s ease-in-out`
- Cursor: `pointer`

### Mobile Touch
- Slight background change: `background #F9FAFB`
- No transform (to avoid janky mobile experience)

---

## Pagination

### Position
- Bottom of job listings
- Centered
- Margin-top: `32px`

### Button Styling
- Size: `40px x 40px`
- Font size: `14px`
- Border: `1px solid #D1D5DB`
- Background: white
- Border radius: `6px`
- Margin: `0 4px`

### Active Page Button
- Background: `#3B82F6` (blue)
- Color: white
- Border-color: `#3B82F6`

### Hover State
- Background: `#F3F4F6` (light gray)
- Cursor: pointer

### Previous/Next Buttons
- Font size: `14px`
- Text: "← Previous" / "Next →"

---

## Footer

### Footer Container
- Background: `#1F2937` (dark gray)
- Color: white
- Padding: `40px 20px`
- Margin-top: `80px`

### Footer Content
- Max-width: `1200px`
- Centered
- Text align: center

### Logo in Footer
- Size: `48px x 48px`
- Margin-bottom: `16px`

### Company Name
- Font size: `16px`
- Font weight: `bold`
- Margin-bottom: `8px`

### Description
- Font size: `14px`
- Color: `#D1D5DB` (light gray)
- Line height: `1.6`
- Margin-bottom: `24px`

### Footer Links
- Font size: `14px`
- Color: `#D1D5DB` (light gray)
- List of links: separated by " • "
- Hover: color becomes white
- Links: "Browse Jobs", "Categories", "About Us", "Contact"

---

## Animations & Transitions

### Card Hover
- Duration: `0.3s`
- Easing: `ease-in-out`
- Properties: `box-shadow`, `transform`

### Button Interactions
- Duration: `0.2s`
- Easing: `ease-in-out`
- Properties: `background-color`, `border-color`

### Modal Slide-in
- Duration: `0.3s`
- Easing: `ease-out`
- Direction: from right to left

### Fade Effects
- Duration: `0.3s`
- Easing: `ease-in-out`
- Opacity: `0` to `1`

---

## Responsive Breakpoints

```
Mobile:   0px - 767px
Tablet:   768px - 1023px
Desktop:  1024px - 1919px
Large:    1920px+
```

### Key Changes by Breakpoint

**Mobile (0-767px)**
- Single column layout
- Filters in modal
- Font sizes reduced by ~10%
- Padding/margins reduced by ~20%
- Header height reduced

**Tablet (768-1023px)**
- Single or two-column (decide based on available space)
- Filters collapsible above listings
- Slightly larger fonts than mobile

**Desktop (1024px+)**
- Full two-column layout with sidebar
- Maximum padding and spacing
- Optimal readability

---

## Accessibility Requirements

- All interactive elements must be keyboard accessible
- Color contrast ratio minimum 4.5:1 for text
- Focus states must be visible (outline or highlight)
- Form labels must be associated with inputs
- Alt text for all images/icons
- Semantic HTML (buttons as `<button>`, links as `<a>`)
- ARIA labels where necessary

---

## General Guidelines

1. **Keep it clean** - whitespace is your friend
2. **Consistency** - use the same spacing, sizing, and colors throughout
3. **Performance** - optimize images and minimize CSS/JS
4. **Accessibility** - test with keyboard navigation and screen readers
5. **Mobile-first approach** - design for mobile, then enhance for desktop
6. **Modern look** - avoid dated elements, use subtle shadows and spacing
7. **User-focused** - prioritize readability and ease of finding jobs

---

## Implementation Notes

- Use CSS Grid or Flexbox for layout (avoid floats)
- Implement media queries for responsive design
- Consider using CSS custom properties (variables) for colors
- Use semantic HTML5 elements
- Minify CSS and optimize assets for production
- Test on real devices and multiple browsers

---

*This prompt is detailed and specific. Use it with Claude Code or in a chat artifact for best results.*
