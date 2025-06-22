
# Observation Checklist Plugin for Moodle

A comprehensive workplace observation checklist plugin for Moodle that allows educators to create, manage, and assess student competencies through structured observation checklists.

## Features

- **Role-based Access Control**: Different interfaces for teachers/assessors and students
- **Dynamic Item Management**: Add, edit, and organize checklist items
- **Progress Tracking**: Visual progress indicators and completion statistics
- **Assessment Tools**: Structured assessment with notes and status tracking
- **Printable Reports**: Generate professional observation reports
- **Bootstrap Integration**: Responsive design using Bootstrap CSS
- **Privacy Compliance**: Full GDPR compliance with privacy API implementation
- **Backup & Restore**: Complete backup and restore functionality
- **Mobile Support**: Basic mobile app compatibility

## Installation

1. Download or clone this repository into your Moodle `mod/` directory:
   ```bash
   cd /path/to/moodle/mod/
   git clone [repository-url] observationchecklist
   ```

2. Log in to your Moodle site as an administrator

3. Navigate to Site Administration > Notifications

4. Follow the installation prompts to install the plugin

5. Configure the plugin settings as needed

## Requirements

- Moodle 4.1 or higher
- PHP 7.4 or higher
- MySQL 5.7 or PostgreSQL 10 or higher

## Usage

### For Teachers/Assessors:

1. **Create a Checklist**: Add the "Observation Checklist" activity to your course
2. **Add Items**: Use the interface to add observation items with categories
3. **Assess Students**: Select students and mark items as satisfactory, not satisfactory, or in progress
4. **Add Notes**: Provide detailed feedback for each assessment
5. **Generate Reports**: Create printable reports for students

### For Students:

1. **View Progress**: See your current assessment status
2. **Submit Evidence**: Upload evidence for assessment (if enabled)
3. **Track Completion**: Monitor your progress towards completion
4. **Review Feedback**: Read assessor notes and recommendations

## Capabilities

- `mod/observationchecklist:addinstance` - Add new checklist instances
- `mod/observationchecklist:view` - View checklist content
- `mod/observationchecklist:edit` - Edit checklist items
- `mod/observationchecklist:assess` - Assess student observations
- `mod/observationchecklist:submit` - Submit evidence for assessment
- `mod/observationchecklist:viewreports` - View observation reports

## Configuration Options

- **Allow Student Add**: Let students add their own items
- **Allow Student Submit**: Enable evidence submission
- **Enable Printing**: Allow report generation and printing

## Database Schema

The plugin uses three main tables:

- `observationchecklist` - Main activity instances
- `observationchecklist_items` - Individual checklist items
- `observationchecklist_user_items` - User progress and assessments

## Development

### File Structure
```
mod/observationchecklist/
├── backup/              # Backup and restore functionality
├── classes/             # Class definitions
├── db/                  # Database definitions
├── lang/                # Language strings
├── templates/           # Mustache templates
├── lib.php              # Main library functions
├── locallib.php         # Local library functions
├── mod_form.php         # Activity configuration form
├── version.php          # Plugin version information
└── view.php             # Main view script
```

### Coding Standards

This plugin follows Moodle coding standards and guidelines:
- PSR-4 autoloading for classes
- Moodle coding style
- Full documentation with PHPDoc
- Privacy API implementation
- Accessibility compliance

## Support

For support, please:
1. Check the Moodle documentation
2. Review the plugin documentation
3. Submit issues through the repository

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

## Changelog

### Version 1.0.0
- Initial release
- Core functionality for observation checklists
- Role-based access control
- Assessment and reporting features
- Mobile compatibility
- Privacy API compliance
