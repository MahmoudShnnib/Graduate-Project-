import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { apiClient } from './client';
import type {
  AddDropOptions,
  AddDropRequest,
  AppNotification,
  ClearanceShow,
  CourseTree,
  Enrollment,
  ExceptionalOptions,
  ExceptionalRequest,
  Semester,
  Student,
  SuspensionOptions,
  SuspensionRequest,
  Survey,
  ThesisShow,
  Course,
} from './types';

type ApiList<T> = { data: T[] };
type ApiItem<T> = { data: T };

// ---------- Generic helpers ----------

async function get<T>(path: string, params?: Record<string, any>): Promise<T> {
  const { data } = await apiClient.get<T>(path, { params });
  return data;
}

async function post<T = any>(path: string, body?: any): Promise<T> {
  const { data } = await apiClient.post<T>(path, body);
  return data;
}

async function del<T = any>(path: string): Promise<T> {
  const { data } = await apiClient.delete<T>(path);
  return data;
}

// ---------- Profile / Auth ----------

export function useMe() {
  return useQuery({
    queryKey: ['me'],
    queryFn: async () => (await get<ApiItem<Student>>('/auth/me')).data,
  });
}

// ---------- Semesters ----------

export function useSemesters() {
  return useQuery({
    queryKey: ['semesters'],
    queryFn: async () => (await get<ApiList<Semester>>('/semesters')).data,
  });
}

export function useActiveSemester() {
  return useQuery({
    queryKey: ['semester', 'active'],
    queryFn: async () => (await get<ApiItem<Semester>>('/semesters/active')).data,
  });
}

// ---------- Enrollments ----------

export function useEnrollments(semesterId?: number) {
  return useQuery({
    queryKey: ['enrollments', semesterId ?? 'active'],
    queryFn: async () =>
      (await get<ApiList<Enrollment>>('/enrollments', semesterId ? { semester_id: semesterId } : undefined)).data,
  });
}

// ---------- Grades ----------

export function useGrades(semesterId?: number) {
  return useQuery({
    queryKey: ['grades', semesterId ?? 'active'],
    queryFn: async () =>
      (await get<ApiList<Enrollment>>('/grades', semesterId ? { semester_id: semesterId } : undefined)).data,
  });
}

// ---------- Course Tree ----------

export function useCourseTree() {
  return useQuery({
    queryKey: ['course-tree'],
    queryFn: async () => (await get<{ data: CourseTree }>('/course-tree')).data,
  });
}

// ---------- Surveys ----------

export function useSurveys(semesterId?: number) {
  return useQuery({
    queryKey: ['surveys', semesterId ?? 'active'],
    queryFn: async () =>
      (await get<ApiList<Survey>>('/surveys', semesterId ? { semester_id: semesterId } : undefined)).data,
  });
}

export function useAvailableSurveyCourses() {
  return useQuery({
    queryKey: ['surveys', 'available'],
    queryFn: async () => (await get<ApiList<Course>>('/surveys/available-courses')).data,
  });
}

export function useSubmitSurvey() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (courseIds: number[]) => post<{ message: string }>('/surveys', { course_ids: courseIds }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['surveys'] }),
  });
}

// ---------- Add/Drop ----------

export function useAddDropOptions() {
  return useQuery({
    queryKey: ['add-drop', 'options'],
    queryFn: () => get<AddDropOptions>('/add-drop-requests/options'),
  });
}

export function useAddDropHistory() {
  return useQuery({
    queryKey: ['add-drop', 'history'],
    queryFn: async () => (await get<ApiList<AddDropRequest>>('/add-drop-requests')).data,
  });
}

export function useCreateAddDrop() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { course_offering_id: number; type: 'add' | 'drop'; reason?: string }) =>
      post<{ message: string }>('/add-drop-requests', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['add-drop'] }),
  });
}

// ---------- Suspension ----------

export function useSuspensionOptions() {
  return useQuery({
    queryKey: ['suspension', 'options'],
    queryFn: () => get<SuspensionOptions>('/suspension-requests/options'),
  });
}

export function useSuspensionHistory() {
  return useQuery({
    queryKey: ['suspension', 'history'],
    queryFn: async () => (await get<ApiList<SuspensionRequest>>('/suspension-requests')).data,
  });
}

export function useCreateSuspension() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { semester_id: number; reason: string }) =>
      post<{ message: string }>('/suspension-requests', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['suspension'] }),
  });
}

// ---------- Thesis ----------

export function useThesis() {
  return useQuery({
    queryKey: ['thesis'],
    queryFn: () => get<ThesisShow>('/thesis'),
  });
}

export function useRegisterThesis() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: {
      title: string;
      title_en?: string;
      is_external_supervisor: boolean;
      supervisor_name?: string;
      supervisor_employer?: string;
      supervisor_degree?: string;
      faculty_supervisor_id?: number;
    }) => post<{ message: string }>('/thesis/register', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['thesis'] }),
  });
}

// ---------- Exceptional ----------

export function useExceptionalOptions() {
  return useQuery({
    queryKey: ['exceptional', 'options'],
    queryFn: () => get<ExceptionalOptions>('/exceptional-requests/options'),
  });
}

export function useExceptionalHistory() {
  return useQuery({
    queryKey: ['exceptional', 'history'],
    queryFn: async () => (await get<ApiList<ExceptionalRequest>>('/exceptional-requests')).data,
  });
}

export function useCreateExceptional() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { course_id: number; opportunity_type: string; reason: string }) =>
      post<{ message: string }>('/exceptional-requests', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['exceptional'] }),
  });
}

// ---------- Clearance ----------

export function useClearance() {
  return useQuery({
    queryKey: ['clearance'],
    queryFn: () => get<ClearanceShow>('/clearance'),
  });
}

// ---------- Notifications ----------

export function useNotifications() {
  return useQuery({
    queryKey: ['notifications'],
    queryFn: async () => (await get<ApiList<AppNotification>>('/notifications')).data,
  });
}

export function useMarkNotificationRead() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => post(`/notifications/${id}/read`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['notifications'] }),
  });
}

export function useMarkAllNotificationsRead() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => post('/notifications/read-all'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['notifications'] }),
  });
}
