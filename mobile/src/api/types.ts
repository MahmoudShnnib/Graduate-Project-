// API response shapes — kept aligned with App\Http\Resources\Api\V1\*.

export type StatusEnum = { value: string; label: string; color?: string };

export type Specialization = { id: number; name: string; code: number };

export type Course = {
  id: number;
  name: string;
  code: string;
  credits: number;
  is_active: boolean;
  type?: 'general' | 'specialized' | 'elective' | null;
};

export type CourseOffering = {
  id: number;
  course?: Course;
  semester_id: number;
  faculty?: { id: number; name: string } | null;
  is_open: boolean;
  coursework_max: number;
  final_max: number;
};

export type Grade = {
  id: number;
  enrollment_id: number;
  coursework_grade: number | null;
  final_grade: number | null;
  total: number | null;
  letter: string | null;
  points: number | null;
  midterm_published_at: string | null;
  published_at: string | null;
  publish_state: 'published' | 'midterm_only' | 'unpublished';
};

export type Enrollment = {
  id: number;
  status: StatusEnum;
  course_offering: CourseOffering;
  grade?: Grade | null;
};

export type Semester = {
  id: number;
  name: string;
  year: number;
  season: StatusEnum;
  start_date: string | null;
  end_date: string | null;
  is_active: boolean;
  survey_open: boolean;
  add_drop_start: string | null;
  add_drop_end: string | null;
  add_drop_open: boolean;
};

export type Warning = {
  id: number;
  type: StatusEnum;
  course?: { id: number; name: string; code: string } | null;
  is_active: boolean;
  created_at: string;
};

export type Student = {
  id: number;
  enrollment_no: string;
  name: string | null;
  name_en: string | null;
  email: string | null;
  phone: string | null;
  national_id: string | null;
  gender: string | null;
  birth_date: string | null;
  admission_year: number | null;
  is_transfer: boolean;
  gpa: number;
  specialization?: Specialization;
  status: StatusEnum;
  warnings_count: number;
  active_warnings?: Warning[];
  can_perform_actions: boolean;
};

export type Survey = {
  id: number;
  course?: Course;
  priority: number;
  is_retake: boolean;
  is_processed: boolean;
  rejected_at: string | null;
  created_at: string;
};

export type AddDropRequest = {
  id: number;
  type: StatusEnum;
  status: StatusEnum;
  reason: string | null;
  course_offering: CourseOffering;
  decided_at: string | null;
  created_at: string;
};

export type SuspensionRequest = {
  id: number;
  semester?: Semester;
  reason: string;
  status: StatusEnum;
  decision_notes: string | null;
  decided_at: string | null;
  created_at: string;
};

export type ThesisRegistration = {
  id: number;
  title: string;
  title_en: string | null;
  supervisor_name: string;
  is_external_supervisor: boolean;
  status: StatusEnum;
  rejection_reason: string | null;
  created_at: string;
  decided_at: string | null;
};

export type DiscussionRequest = {
  id: number;
  thesis_registration_id: number;
  status: StatusEnum;
  discussion_date: string | null;
  rejection_reason: string | null;
  discussed_at: string | null;
  completed_at: string | null;
  created_at: string;
};

export type ExceptionalRequest = {
  id: number;
  course?: Course;
  opportunity_type: { value: string | null; label: string | null };
  reason: string;
  status: StatusEnum;
  decision_notes: string | null;
  decided_at: string | null;
  created_at: string;
};

export type AppNotification = {
  id: number;
  type: 'info' | 'warning';
  title: string;
  body: string;
  is_read: boolean;
  read_at: string | null;
  created_at: string;
};

export type Eligibility = {
  eligible: boolean;
  gpa: { value: number; required: number; met: boolean };
  general: { required: number; completed: number };
  specialized: { required: number; completed: number };
  elective: { required: number; completed: number };
  no_failures: boolean;
};

export type ThesisShow = {
  eligibility: Eligibility;
  registration: ThesisRegistration | null;
  discussion: DiscussionRequest | null;
  phase: 0 | 1 | 2 | 3 | 4;
};

export type CourseTreeRow = {
  id: number;
  name: string;
  code: string;
  credits: number;
  type: 'general' | 'specialized' | 'elective';
  status: 'passed' | 'enrolled' | 'failed' | 'not_taken';
};

export type CourseTree = {
  general: CourseTreeRow[];
  specialized: CourseTreeRow[];
  elective: CourseTreeRow[];
};

export type AddDropOptions = {
  window_open: boolean;
  usage: { add: number; drop: number; max: number };
  addable: { data: CourseOffering[] };
  droppable: { data: Enrollment[] };
};

export type SuspensionOptions = {
  state: 'available' | 'pending_block' | 'approved_block';
  pending: SuspensionRequest | null;
  semesters: { id: number; name: string; disabled: boolean; reason: string | null }[];
};

export type ExceptionalOptions = {
  failed_courses: { data: Course[] };
  opportunity_types: { value: string; label: string }[];
};

export type ClearanceShow = {
  eligible: boolean;
  request: { id: number; status: string; form_path: string | null; issued_at: string | null; created_at: string } | null;
  discussion_phase: string | null;
  student: {
    name: string | null;
    enrollment_no: string;
    specialization: string | null;
    supervisor_name: string | null;
  };
};
