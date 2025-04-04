# Changelog

## [0.3.4] - 2024-01-12

### Added
- Integrated LLM (Large Language Model) for advanced schedule optimization.
- LLM analyzes user preferences and events to provide intelligent suggestions.
- Updated `optimize.php` to query the LLM and apply its recommendations.

### Enhanced
- Optimization algorithm now incorporates LLM-generated insights.
- Improved event rescheduling logic with AI-driven decisions.

### Technical Details
- Added LLM API integration in `optimize.php`.
- Enhanced database update logic to reflect LLM-optimized changes.

## [0.3.3] - 2024-01-11

### Added
- AI-powered schedule optimization with Mistral model
- Advanced optimization preferences including learning style
- Interactive optimization results UI with metrics
- Schedule health visualization
- Transaction-safe change application system

### Enhanced
- Schedule optimization algorithm
- Event timing intelligence
- Break period management
- Learning style adaptation
- Real-time schedule analysis

## [0.3.2] - 2024-01-11

### Added
- Schedule optimization endpoint
- Time-based event rescheduling
- Schedule health metrics
- Optimization preferences UI

### Fixed
- Optimize button functionality
- Event listener initialization
- Time range validation

## [0.3.1] - 2024-01-11

### Added
- Default admin user for system initialization
- Session handling improvements
- User authentication fixes
- Error handling for invalid user states

### Fixed
- Invalid user ID issues in preferences
- Event listener null reference errors
- Session initialization order
- Added missing database seed data

## [0.3.0] - 2024-01-11

### Added
- Event management functionality
- Database tables for events and categories
- Event creation modal dialog
- Event display on calendar
- Category system for events
- API endpoint for saving events
- SweetAlert2 integration for dialogs

## [0.2.0] - 2024-01-10

### Added
- Basic calendar view implementation
- Month navigation system
- Interactive calendar grid layout
- Today's date highlighting
- Responsive calendar design
- Click handlers for calendar days
- Month and year display
- Previous/Next month navigation

### Removed
- Authentication system
- Login functionality
- User role management
- Dashboard interfaces

### Technical Details
- Simplified application to focus on calendar functionality
- Improved UI with Tailwind CSS
- Added date manipulation functions
- Streamlined codebase

## [0.1.0] - 2024-01-09

### Added
- Initial project setup
- Database schema design
- Basic component structure
- Authentication system implementation
- Navigation bar component
- Data display tables for students, classrooms, and schools
- CRUD operations implementation
- Tailwind CSS integration for modern UI
- Basic animations and transitions
- Database connection setup
- Reusable components (buttons, cards, tables)

### Changed
- Updated UI to match Vercel-style design
- Implemented rounded corners and soft shadows
- Enhanced form layouts for better UX

### Technical Details
- PHP backend implementation
- MySQL database integration
- Tailwind CSS for styling
- JavaScript for dynamic interactions