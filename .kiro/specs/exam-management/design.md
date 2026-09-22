# Exam Management System Design Document

## Overview

The Exam Management System is designed as a comprehensive Laravel module that integrates seamlessly with the existing academic portal. It provides flexible grading configurations, efficient mark entry methods, automated grade calculations, and detailed reporting capabilities. The system follows Laravel's modular architecture using nwidart/laravel-modules and integrates with the existing Inertia.js + React frontend.

## Architecture

### System Architecture Pattern
- **Modular Architecture**: Implemented as a Laravel module (`Modules/ExamManagement`)
- **MVC Pattern**: Following Laravel's Model-View-Controller architecture
- **Service Layer Pattern**: Business logic encapsulated in service classes
- **Repository Pattern**: Data access abstraction for complex queries
- **Event-Driven Architecture**: Using Laravel events for audit trails and notifications

### Technology Stack
- **Backend**: Laravel 12.x with PHP 8.2+
- **Frontend**: React 18+ with Inertia.js 2.0
- **Database**: SQLite/MySQL with Eloquent ORM
- **Styling**: Tailwind CSS (consistent with existing layout)
- **File Processing**: Laravel Excel for CSV/Excel imports
- **PDF Generation**: Laravel DomPDF for report generation

### Integration Points
- **Academic Portal**: Extends existing AcademicLayout.jsx
- **Authentication**: Integrates with existing auth system
- **Role Management**: Uses existing role-based permissions
- **Academic Data**: Connects with existing academic year, term, grade level, and subject models

## Components and Interfaces

### Core Models

#### GradingScheme Model
```php
class GradingScheme extends Model
{
    // Properties: name, type (letter/percentage/units/custom), academic_year_id, is_active
    // Relationships: hasMany(GradingRule), belongsTo(AcademicYear)
    // Methods: calculateGrade($marks, $totalMarks), validateThresholds()
}
```

#### GradingRule Model
```php
class GradingRule extends Model
{
    // Properties: grading_scheme_id, grade_label, min_threshold, max_threshold, grade_point
    // Relationships: belongsTo(GradingScheme)
    // Methods: isInRange($score), getGradePoint()
}
```

#### Assessment Model
```php
class Assessment extends Model
{
    // Properties: name, type, subject_id, grade_level_id, class_id, grading_scheme_id, total_marks, weightage, due_date
    // Relationships: belongsTo(Subject, GradeLevel, GradingScheme), hasMany(StudentMark)
    // Methods: calculateClassAverage(), getGradeDistribution()
}
```

#### StudentMark Model
```php
class StudentMark extends Model
{
    // Properties: assessment_id, student_id, marks_obtained, calculated_grade, status (present/absent/exempt)
    // Relationships: belongsTo(Assessment, Student), morphMany(AuditLog)
    // Methods: calculateGrade(), updateGradeHistory()
}
```

### Service Classes

#### GradingService
- **Purpose**: Handles all grading calculations and scheme management
- **Key Methods**:
  - `calculateGrade($marks, $totalMarks, $gradingScheme)`
  - `validateGradingScheme($rules)`
  - `getGradeDistribution($assessmentId)`
  - `calculateWeightedAverage($studentId, $termId)`

#### MarkEntryService
- **Purpose**: Manages individual and bulk mark entry operations
- **Key Methods**:
  - `enterIndividualMark($assessmentId, $studentId, $marks)`
  - `processBulkUpload($file, $assessmentId)`
  - `validateUploadData($data, $assessment)`
  - `generateUploadReport($results)`

#### ReportService
- **Purpose**: Generates various academic reports and analytics
- **Key Methods**:
  - `generateStudentReport($studentId, $termId)`
  - `generateClassReport($classId, $termId)`
  - `generateGradeLevelReport($gradeLevelId, $termId)`
  - `exportReportToPDF($reportData, $format)`

#### AnalyticsService
- **Purpose**: Provides performance analytics and statistical calculations
- **Key Methods**:
  - `calculateClassStatistics($classId, $assessmentId)`
  - `getPerformanceTrends($studentId, $dateRange)`
  - `generateComparativeAnalysis($gradeLevelId)`
  - `getSubjectPerformanceMetrics($subjectId)`

### Frontend Components

#### GradingSchemeManager Component
- **Purpose**: Configure and manage grading schemes
- **Features**: 
  - Create/edit grading schemes
  - Define grade thresholds with validation
  - Preview grade calculations
  - Manage multiple active schemes

#### AssessmentManager Component
- **Purpose**: Create and manage assessments
- **Features**:
  - Assessment creation wizard
  - Link to grading schemes
  - Set weightages and due dates
  - Assessment status tracking

#### MarkEntryInterface Component
- **Purpose**: Individual mark entry with real-time calculations
- **Features**:
  - Student list with photos
  - Real-time grade calculation
  - Attendance status management
  - Previous assessment context

#### BulkUploadInterface Component
- **Purpose**: CSV/Excel file upload and processing
- **Features**:
  - File upload with validation
  - Data preview and error reporting
  - Batch processing with progress tracking
  - Upload summary and error logs

#### AnalyticsDashboard Component
- **Purpose**: Performance analytics and visualizations
- **Features**:
  - Interactive charts and graphs
  - Filtering and drill-down capabilities
  - Export functionality
  - Comparative analysis views

#### ReportGenerator Component
- **Purpose**: Generate and export various reports
- **Features**:
  - Report template selection
  - Custom date ranges and filters
  - Multiple export formats
  - Scheduled report generation

## Data Models

### Database Schema Design

#### grading_schemes Table
```sql
CREATE TABLE grading_schemes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('letter', 'percentage', 'units', 'custom') NOT NULL,
    academic_year_id BIGINT,
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);
```

#### grading_rules Table
```sql
CREATE TABLE grading_rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    grading_scheme_id BIGINT NOT NULL,
    grade_label VARCHAR(10) NOT NULL,
    min_threshold DECIMAL(5,2) NOT NULL,
    max_threshold DECIMAL(5,2) NOT NULL,
    grade_point DECIMAL(3,2),
    description VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (grading_scheme_id) REFERENCES grading_schemes(id) ON DELETE CASCADE
);
```

#### assessments Table
```sql
CREATE TABLE assessments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('exam', 'test', 'quiz', 'assignment', 'project') NOT NULL,
    subject_id BIGINT NOT NULL,
    grade_level_id BIGINT NOT NULL,
    class_id BIGINT,
    grading_scheme_id BIGINT NOT NULL,
    academic_year_id BIGINT NOT NULL,
    term_id BIGINT,
    total_marks DECIMAL(6,2) NOT NULL,
    weightage DECIMAL(5,2) DEFAULT 100.00,
    due_date DATE,
    instructions TEXT,
    status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
    created_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id),
    FOREIGN KEY (grading_scheme_id) REFERENCES grading_schemes(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

#### student_marks Table
```sql
CREATE TABLE student_marks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    assessment_id BIGINT NOT NULL,
    student_id BIGINT NOT NULL,
    marks_obtained DECIMAL(6,2),
    calculated_grade VARCHAR(10),
    grade_point DECIMAL(3,2),
    status ENUM('present', 'absent', 'exempt') DEFAULT 'present',
    remarks TEXT,
    entered_by BIGINT,
    entered_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (entered_by) REFERENCES users(id),
    UNIQUE KEY unique_student_assessment (assessment_id, student_id)
);
```

#### grade_calculations Table
```sql
CREATE TABLE grade_calculations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_id BIGINT NOT NULL,
    subject_id BIGINT NOT NULL,
    term_id BIGINT,
    academic_year_id BIGINT NOT NULL,
    total_marks DECIMAL(8,2),
    weighted_average DECIMAL(5,2),
    final_grade VARCHAR(10),
    final_grade_point DECIMAL(3,2),
    calculation_date TIMESTAMP,
    calculated_by BIGINT,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (calculated_by) REFERENCES users(id)
);
```

### Data Relationships
- **One-to-Many**: GradingScheme → GradingRules, Assessment → StudentMarks
- **Many-to-One**: Assessment → Subject/GradeLevel/GradingScheme, StudentMark → Student/Assessment
- **Many-to-Many**: Students ↔ Assessments (through StudentMarks)

### Data Validation Rules
- **Grading Thresholds**: Must not overlap, must cover complete range (0-100% or equivalent)
- **Mark Entry**: Cannot exceed assessment total marks, must be numeric
- **Grade Calculations**: Automatically validated against grading scheme rules
- **File Uploads**: CSV/Excel format validation, required columns check

## Error Handling

### Validation Errors
- **Client-Side**: Real-time validation with immediate feedback
- **Server-Side**: Comprehensive validation with detailed error messages
- **File Upload**: Line-by-line error reporting with specific issue identification

### Business Logic Errors
- **Grade Calculation**: Fallback to manual grade entry if automatic calculation fails
- **Data Integrity**: Transaction rollback for bulk operations on failure
- **Concurrent Access**: Optimistic locking for mark entry conflicts

### System Errors
- **Database Failures**: Graceful degradation with user-friendly error messages
- **File Processing**: Partial success handling with detailed error logs
- **Export Failures**: Alternative format options and retry mechanisms

### Error Logging and Monitoring
- **Audit Trail**: Complete logging of all mark entries and modifications
- **Performance Monitoring**: Query optimization and slow operation alerts
- **User Activity**: Comprehensive activity logs for security and debugging

## Testing Strategy

### Unit Testing
- **Model Tests**: Validation rules, relationships, and business logic methods
- **Service Tests**: Grade calculations, mark processing, and report generation
- **Utility Tests**: File processing, data validation, and calculation algorithms

### Integration Testing
- **API Tests**: All controller endpoints with various input scenarios
- **Database Tests**: Complex queries, transactions, and data integrity
- **File Upload Tests**: CSV/Excel processing with various file formats and error conditions

### Feature Testing
- **End-to-End Workflows**: Complete mark entry and grade calculation processes
- **User Interface Tests**: React component functionality and user interactions
- **Report Generation**: PDF/Excel export functionality and data accuracy

### Performance Testing
- **Load Testing**: Bulk mark upload with large datasets
- **Stress Testing**: Concurrent user access during peak usage
- **Database Performance**: Query optimization for large datasets

### Security Testing
- **Authorization Tests**: Role-based access control validation
- **Data Protection**: Sensitive academic data handling and export controls
- **Input Validation**: SQL injection and XSS prevention testing

## Implementation Considerations

### Performance Optimization
- **Database Indexing**: Optimized indexes for frequent queries (student_id, assessment_id, academic_year_id)
- **Caching Strategy**: Redis caching for grading schemes and frequently accessed data
- **Lazy Loading**: Efficient data loading for large student lists and assessment data
- **Background Processing**: Queue-based processing for bulk operations and report generation

### Scalability Considerations
- **Modular Design**: Easy extension for additional grading schemes and assessment types
- **API Design**: RESTful APIs for potential mobile app integration
- **Database Sharding**: Preparation for multi-tenant or large-scale deployments
- **Microservice Ready**: Loosely coupled design for future service separation

### Security Measures
- **Role-Based Access**: Granular permissions for different user types (teachers, administrators)
- **Data Encryption**: Sensitive academic data encryption at rest and in transit
- **Audit Logging**: Comprehensive audit trails for all academic data modifications
- **Input Sanitization**: Protection against malicious file uploads and data injection

### Maintenance and Monitoring
- **Health Checks**: System health monitoring and alerting
- **Data Backup**: Automated backup strategies for critical academic data
- **Version Control**: Database migration strategies for schema updates
- **Documentation**: Comprehensive API documentation and user guides
