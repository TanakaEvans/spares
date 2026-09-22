# Requirements Document

## Introduction

The Exam Management System is a comprehensive feature that enables educational institutions to configure flexible grading systems, manage student assessments, record marks, and generate detailed academic reports. The system supports various grading schemes (letter grades, percentage, units, etc.) with customizable thresholds and provides multiple methods for mark entry including individual input and bulk CSV/Excel uploads. It automatically calculates student grades, class averages, and generates comprehensive reports for students, classes, and grade levels.

## Requirements

### Requirement 1: Grading System Configuration

**User Story:** As an academic administrator, I want to configure flexible grading systems with customizable grade scales and thresholds, so that the system can accommodate different institutional grading policies.

#### Acceptance Criteria

1. WHEN an administrator accesses the grading configuration THEN the system SHALL display options to create multiple grading schemes
2. WHEN creating a grading scheme THEN the system SHALL allow selection of grade types (letter grades A-F, percentage, units, numerical scores, custom labels)
3. WHEN setting grade thresholds THEN the system SHALL accept both percentage ranges (70%-100%) and absolute score ranges (70-100 points)
4. WHEN configuring letter grades THEN the system SHALL allow custom grade labels (A, B, C, D, F or Distinction, Credit, Pass, Fail)
5. WHEN setting up grading rules THEN the system SHALL validate that thresholds do not overlap and cover the complete range
6. WHEN saving grading configurations THEN the system SHALL store multiple active grading schemes per academic year/term

### Requirement 2: Exam and Assessment Management

**User Story:** As a teacher or academic coordinator, I want to create and manage various types of assessments with specific grading schemes, so that I can properly evaluate student performance across different subjects and assessment types.

#### Acceptance Criteria

1. WHEN creating an assessment THEN the system SHALL allow specification of exam type (midterm, final, quiz, assignment, project)
2. WHEN setting up an exam THEN the system SHALL require selection of subject, grade level, class, and applicable grading scheme
3. WHEN configuring assessment details THEN the system SHALL accept total marks, weightage percentage, and due dates
4. WHEN creating assessments THEN the system SHALL allow association with specific academic terms and years
5. WHEN managing exams THEN the system SHALL provide options to edit, duplicate, or archive assessments
6. WHEN viewing assessments THEN the system SHALL display all relevant exam details and current status

### Requirement 3: Individual Mark Entry

**User Story:** As a teacher, I want to enter student marks individually with real-time grade calculation, so that I can efficiently record assessment results and immediately see the computed grades.

#### Acceptance Criteria

1. WHEN accessing mark entry THEN the system SHALL display a list of students enrolled in the selected class/subject
2. WHEN entering marks THEN the system SHALL validate input against the maximum possible marks for the assessment
3. WHEN a mark is entered THEN the system SHALL automatically calculate and display the corresponding grade based on the configured grading scheme
4. WHEN saving individual marks THEN the system SHALL update the student's academic record immediately
5. WHEN entering marks THEN the system SHALL provide options to mark students as absent or exempt
6. WHEN viewing mark entry interface THEN the system SHALL show student photos, names, and previous assessment scores for context

### Requirement 4: Bulk Mark Upload

**User Story:** As a teacher, I want to upload student marks in bulk using CSV or Excel files, so that I can efficiently enter large amounts of assessment data without manual individual entry.

#### Acceptance Criteria

1. WHEN uploading marks THEN the system SHALL accept CSV and Excel file formats
2. WHEN processing upload files THEN the system SHALL validate file structure and required columns (student ID, marks)
3. WHEN validating uploaded data THEN the system SHALL check for duplicate entries, invalid marks, and missing students
4. WHEN upload validation fails THEN the system SHALL display detailed error messages with line numbers and specific issues
5. WHEN upload is successful THEN the system SHALL show a preview of data before final confirmation
6. WHEN confirming bulk upload THEN the system SHALL process all valid entries and generate a summary report of successful and failed entries

### Requirement 5: Automated Grade Calculation

**User Story:** As an academic administrator, I want the system to automatically calculate student grades based on configured rules, so that grading is consistent and accurate across all assessments.

#### Acceptance Criteria

1. WHEN marks are entered THEN the system SHALL automatically apply the appropriate grading scheme to calculate letter grades or grade points
2. WHEN calculating grades THEN the system SHALL handle different grading schemes (percentage-based, point-based, custom thresholds)
3. WHEN processing multiple assessments THEN the system SHALL calculate weighted averages based on assessment weightings
4. WHEN computing final grades THEN the system SHALL consider all completed assessments for the term/semester
5. WHEN grade calculations change THEN the system SHALL update all affected student records and maintain calculation history
6. WHEN displaying grades THEN the system SHALL show both raw marks and calculated grades with clear indication of the applied grading scheme

### Requirement 6: Student Performance Analytics

**User Story:** As a teacher or administrator, I want to view comprehensive analytics of student performance at individual, class, and grade level, so that I can identify trends and make informed academic decisions.

#### Acceptance Criteria

1. WHEN viewing individual student performance THEN the system SHALL display all assessment scores, grades, and performance trends over time
2. WHEN analyzing class performance THEN the system SHALL calculate and display class averages, highest/lowest scores, and grade distribution
3. WHEN reviewing grade level analytics THEN the system SHALL provide comparative analysis across multiple classes and subjects
4. WHEN generating performance reports THEN the system SHALL include statistical measures (mean, median, standard deviation)
5. WHEN viewing analytics THEN the system SHALL provide filtering options by date range, subject, assessment type, and student groups
6. WHEN displaying performance data THEN the system SHALL include visual charts and graphs for easy interpretation

### Requirement 7: Academic Report Generation

**User Story:** As an academic administrator, I want to generate comprehensive academic reports for students, classes, and institutional analysis, so that stakeholders can access detailed academic performance information.

#### Acceptance Criteria

1. WHEN generating student reports THEN the system SHALL include all assessment scores, calculated grades, and performance summaries
2. WHEN creating class reports THEN the system SHALL provide detailed class performance analysis with individual student breakdowns
3. WHEN producing institutional reports THEN the system SHALL aggregate data across all grade levels and provide comparative analysis
4. WHEN generating reports THEN the system SHALL support multiple output formats (PDF, Excel, CSV)
5. WHEN customizing reports THEN the system SHALL allow selection of specific data fields, date ranges, and formatting options
6. WHEN accessing reports THEN the system SHALL provide role-based access control ensuring appropriate data visibility

### Requirement 8: Data Security and Audit Trail

**User Story:** As an academic administrator, I want comprehensive audit trails and data security measures for all exam and grading activities, so that academic integrity is maintained and all changes are traceable.

#### Acceptance Criteria

1. WHEN any mark is entered or modified THEN the system SHALL log the user, timestamp, and nature of the change
2. WHEN accessing student academic data THEN the system SHALL enforce role-based permissions and data access controls
3. WHEN generating audit reports THEN the system SHALL provide detailed logs of all grading activities and system changes
4. WHEN data is modified THEN the system SHALL maintain version history of all grade changes with rollback capabilities
5. WHEN exporting data THEN the system SHALL log all data export activities and apply appropriate security measures
6. WHEN system errors occur THEN the system SHALL maintain data integrity and provide clear error reporting without data loss
