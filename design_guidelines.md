# Design Guidelines: B2B Marketing Assessment - 4R-System

## Design Approach
**Reference-Based Approach**: Inspired by modern SaaS assessment tools like Typeform, HubSpot assessments, and Linear's clean UI patterns. The design emphasizes clarity, progression, and trust-building through professional branding.

## Brand Identity & Color System

### Color Palette
**Primary Colors:**
- Black: `0 0% 0%` - Primary text, backgrounds, bold elements
- White: `0 0% 100%` - Clean backgrounds, contrast elements
- Accent Yellow: `#f7e74f` converted to `54 93% 64%` - CTAs, highlights, active states

**Supporting Colors (Dark Mode):**
- Dark Background: `0 0% 7%` - Main container backgrounds
- Dark Surface: `0 0% 12%` - Card backgrounds
- Border Gray: `0 0% 20%` - Subtle dividers

## Typography

### Font Families
- **Headlines/Display**: "area-extended" - Used for question titles, section headers, results
- **Body/UI**: "area-normal" - All body text, labels, descriptions, buttons
- Load both via @font-face or webfont service

### Type Scale
- Section Titles: 32px-40px (area-extended, bold)
- Question Text: 20px-24px (area-extended, medium)
- Body Text: 16px (area-normal, regular)
- Labels/Small: 14px (area-normal, regular)

## Layout System

### Spacing Units
Primary spacing scale using Tailwind units: **4, 6, 8, 12, 16, 24** (e.g., p-4, gap-6, mt-8, py-12)

### Container Strategy
- Main questionnaire: `max-w-3xl mx-auto` - Focused reading width
- Progress bar: Full width with inner `max-w-4xl`
- Results cards: `max-w-5xl` - Allow for multi-column layouts

### Multi-Step Layout
- Vertical progression with fade transitions
- Step indicator at top showing phase progress (Context → Reach → Relate → Respond → Refine)
- Question cards centered with generous padding (p-8 to p-12)

## Component Design

### Buttons (Per Screenshots)
- Primary CTA: Yellow (#f7e74f) background, black text, significant border-radius (rounded-2xl or rounded-3xl)
- Secondary: Black background with yellow border, yellow text
- Padding: px-8 py-4 for comfortable touch targets
- Font: area-normal, 16px, medium weight
- Hover: Subtle scale (scale-105) or brightness adjustment

### Question Cards
- Background: Dark surface with border-radius (rounded-3xl)
- Border: Subtle gray or yellow accent on active
- Padding: p-8 to p-12
- Shadow: Soft elevation for depth

### Input Styles
- Radio/Checkbox buttons: Custom styled with yellow accent
- Active state: Yellow border + subtle yellow glow
- Border-radius: rounded-xl for all form elements
- Generous spacing between options (gap-4)

### Colorful Shapes (Brand Element)
- Decorative geometric shapes: circles, rounded rectangles
- Colors: Yellow accent with opacity variations
- Placement: Background elements, section dividers, result page decorations
- Border-radius: Consistent with brand (rounded-2xl to rounded-full)

## Interactions & Microinteractions

### Transitions
- Question transitions: Fade + slight translate-y (300ms ease)
- Button states: Transform scale + color transition (200ms)
- Progress bar: Smooth width animation (400ms ease-out)

### Hover States
- Buttons: Scale 1.02-1.05, brightness adjustment
- Option cards: Yellow border glow, subtle background lift
- No animations on mobile (hover unavailable)

### Progress Indicator
- Visual bar showing completion (0-100%)
- Phase labels: Context → Reach → Relate → Respond → Refine
- Yellow fill for completed, gray for remaining
- Border-radius: rounded-full

## Results Page Design

### Layout Structure
- Hero section: Overall score with circular progress indicator
- Phase breakdown: 4-column grid on desktop (stack on mobile)
- Score cards: Individual phase scores with yellow accents
- Recommendations: Text blocks with yellow bullet points
- Decorative shapes scattered as visual interest

### Score Visualization
- Circular/radial progress: Yellow stroke on dark background
- Percentage numbers: Large (area-extended, 48px+)
- Phase scores: Smaller cards with icons and border-radius

## Responsive Behavior
- Desktop (lg): Multi-column results, side-by-side navigation
- Tablet (md): 2-column grids, stacked questions
- Mobile (base): Single column, full-width cards, larger touch targets

## Accessibility
- Consistent dark theme throughout
- High contrast: Yellow on black, white on black
- Focus states: Yellow outline (2px)
- Keyboard navigation: Tab through options, Enter to select
- Screen reader labels: All form inputs properly labeled

## Images
No hero images required. Assessment tools focus on functionality and clarity. Use decorative geometric shapes and brand elements instead for visual interest.