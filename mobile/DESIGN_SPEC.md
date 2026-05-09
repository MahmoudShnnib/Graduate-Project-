# DESIGN_SPEC.md — بوابة الطالب (Student Portal)

A mobile app for graduate students at a Libyan university faculty. The app is the student-facing companion to an existing web academic management system covering enrollment, grades, requests, and thesis tracking.

---

## CORE DESIGN RULES (apply to every screen)

**Layout direction:** RTL (right-to-left) throughout. All content flows from right to left. All text is right-aligned. Navigation icons are mirrored appropriately.

**Style:** Clean, professional, institutional. Think high-quality Arabic government or banking service app — formal and trustworthy, not playful. No glassmorphism, no clay, no gradients on cards. Flat surfaces, clear hierarchy, generous whitespace.

**Platform:** iOS and Android (use native patterns where appropriate — bottom tab bar on iOS, labeled icons).

**Language:** All UI text, labels, headings, buttons, and placeholders are in Arabic. English appears only in the enrollment number field (e.g. "26CS101").

**Font:** Noto Kufi Arabic — used everywhere. Weights: 700 for headings, 500 for labels and buttons, 400 for body and secondary text.

**Colors:**

| Token            | Hex       | Usage                                        |
|------------------|-----------|----------------------------------------------|
| Primary blue     | `#2563EB` | CTA buttons, active tab, links               |
| Secondary blue   | `#3B82F6` | Secondary actions, icon fills                |
| Accent orange    | `#F97316` | Pending badges, warning chips                |
| Background       | `#F8FAFC` | All screen backgrounds                       |
| Surface white    | `#FFFFFF` | Cards, modals, input fields                  |
| Primary text     | `#1E293B` | Headings, labels                             |
| Muted text       | `#64748B` | Secondary info, timestamps                   |
| Border           | `#E2E8F0` | Card borders, dividers                       |
| Success green    | `#16A34A` | Approved, enrolled, completed                |
| Danger red       | `#DC2626` | Rejected, blocked, dismissed                 |
| Warning amber    | `#D97706` | Pending, inactive, on-hold                   |
| Info sky         | `#0EA5E9` | Informational states                         |

**Touch targets:** All interactive elements minimum 48×48pt. Card rows minimum 56pt tall.

**Status banners:** When the student's account is blocked (status = OnHold / Inactive / Dismissed), show a full-width colored banner at the top of every screen below the header explaining the restriction. OnHold = amber banner, Inactive = orange banner, Dismissed = red banner. The banner has an icon on the right, 2 lines of Arabic text, and no close button.

---

## NAVIGATION (Bottom Tab Bar)

5 tabs, labels below icons, active tab uses Primary blue:

- **Tab 1 (rightmost):** الرئيسية — home icon
- **Tab 2:** مقرراتي — book-open icon
- **Tab 3:** الطلبات — document-text icon (badge showing pending count when > 0)
- **Tab 4:** الدرجات — academic-cap icon
- **Tab 5 (leftmost):** حسابي — user-circle icon

---

## SCREEN 1 — الرئيسية (Dashboard)

**Header:** university logo top-right, greeting text "مرحباً، [اسم الطالب]" in 22px bold, enrollment number below in muted text "رقم القيد: 26CS101" in 14px.

**Status chip:** a pill-shaped chip below the greeting showing the student's current academic status with a colored dot. Example states to show: "مستمر" (green dot), "قيده موقوف" (amber dot).

**Semester info card:** White card, rounded-xl, subtle border. Shows active semester name "الفصل الدراسي الأول 2026" and a row of 3 mini-stats side by side: enrolled courses count, pending requests count, GPA. Each stat has a number in 22px bold and a label in 12px muted.

**Warnings section (conditional):** If the student has active warnings, show a warning card with an amber left border, warning icon, and Arabic text describing the warning type. Two possible types: "تحذير رسوب في مقرر" or "تحذير انقطاع متكرر". Show max 2 warnings stacked.

**Quick actions grid:** 2×2 grid of white cards with icon + Arabic label. Items: "استطلاع المقررات", "طلب إضافة/حذف", "درجاتي", "تسجيل الرسالة".

**Recent notifications list:** Section title "آخر الإشعارات" with 2-3 list rows, each with a circle icon (blue for info, amber for warning), notification text, and relative timestamp on the left in muted text.

---

## SCREEN 2 — مقرراتي (My Courses)

**Screen title** "مقرراتي" in page header.

**Semester selector:** Horizontal scrollable chip row at the top showing semester names. Active chip uses primary blue fill, others are outlined.

**Enrolled courses list:** Each row is a white card with:
- Right side: course name in 16px bold, course code below in 14px muted
- Left side: status pill badge — "مسجل" (green), "مسقط" (red), "منسحب" (orange)
- Below the name: instructor name in 12px muted, course type chip (إجباري / اختياري)

**Empty state:** if no enrollments, centered illustration of an open book with Arabic text "لم يتم تسجيلك في أي مقرر لهذا الفصل" and a button "استعرض شجرة المقررات".

---

## SCREEN 3 — شجرة المقررات (Course Tree)

**Screen title** "شجرة المقررات".

**Specialization selector:** Segmented control or dropdown showing the student's specialization.

**Grouped list by course type:** Two collapsible sections — "المقررات الإجبارية" and "المقررات الاختيارية". Section header has a chevron-down icon on the left for collapse/expand.

**Each course row:**
- Right: course name (bold), course code below (muted)
- Left: status icon — checkmark circle (green = passed), clock circle (amber = enrolled), dash circle (muted = not yet taken), X circle (red = failed)
- Tapping a row expands an inline detail showing credit hours, description, prerequisites

---

## SCREEN 4 — استطلاع المقررات (Course Survey)

Show a state-aware header banner first:
- If survey is open: green banner "الاستطلاع مفتوح — أضف المقررات التي ترغب بتسجيلها" with a calendar icon
- If survey is closed: muted gray banner "الاستطلاع مغلق حالياً"
- If student is not Active: amber banner "يجب أن يكون حسابك نشطاً لتقديم الاستطلاع"

**Selected courses section:** Title "المقررات المختارة". Each selected course shown as a white card with course name, code, and a remove button (red trash icon) on the left. Drag handle on right for reordering.

**Available courses section:** Title "المقررات المتاحة". List of available course offerings as selectable rows with a + button on the left. Tapping + moves it to Selected.

**Submit button:** Full-width primary blue button at the bottom "تقديم الاستطلاع". Disabled state when survey is closed.

---

## SCREEN 5 — الإضافة والحذف (Add/Drop Requests)

**Window status banner at the top:**
- Green = "نافذة الإضافة والحذف مفتوحة"
- Red = "نافذة الإضافة والحذف مغلقة"

**Usage counter row:** Two pills side by side showing "الإضافات: 1/3" and "الحذف: 2/3" — amber when limit approaching, red when limit reached.

**Tabs:** Two tabs "طلب جديد" and "السجل" (history)

**New Request tab:**
- Request type toggle: "إضافة" (blue) / "حذف" (red)
- Course selector: a styled dropdown showing available courses to add or enrolled courses to drop
- Reason textarea (optional for drop, required for add)
- Submit button

**History tab:**
- List of past requests, each row: request type chip (إضافة/حذف), course name, status badge (قيد المراجعة/مقبول/مرفوض), timestamp

---

## SCREEN 6 — الدرجات (Grades)

**GPA card at top:** Large white card with the student's cumulative GPA shown in a circular progress gauge (0–4 scale). "المعدل التراكمي" label above, GPA number in 32px bold below. Color-coded: green ≥ 3.0, amber 2.0–2.99, red < 2.0.

**Semester tabs:** Horizontal scrollable tab row for semester selection.

**Grades list:** For the selected semester, list each course as a card:
- Course name (bold, right)
- Two rows of score pills: "أعمال السنة: 45/60" and "الامتحان النهائي: 32/40" — shown only when published
- Total score chip: large colored badge on the left — letter grade (A+, B, etc.) with numeric
- "لم تُنشر الدرجة بعد" muted placeholder if grade not yet published
- "نُشرت أعمال السنة فقط" amber note if only midterm is published

---

## SCREEN 7 — طلب إيقاف قيد (Suspension Request)

Show one of three top states:

- **State A — Approved block:** Red banner with no-entry icon "تمت الموافقة على طلب انقطاع سابق — لا يمكن تقديم طلب جديد". Form is hidden.
- **State B — Pending block:** Amber banner with clock icon "لديك طلب انقطاع قيد المراجعة". Form is hidden. Show the pending request details card below the banner.
- **State C — Available:** Form is shown.

**Form (State C only):**
- Semester picker: styled dropdown of available semesters; semesters with prior rejected requests shown with a lock icon and are not selectable
- Reason textarea: labeled "سبب طلب الانقطاع" with min 10 character note
- Submit button: primary blue "تقديم الطلب"

**Request history section** (always shown at bottom): collapsible, shows past requests as list rows with semester name, status badge, and date.

---

## SCREEN 8 — تسجيل الرسالة والمناقشة (Thesis & Discussion)

This screen has a vertical stepper at the top showing the 4 phases with icons and connecting lines:

- **Step 1:** تسجيل الرسالة (Thesis Registration)
- **Step 2:** طلب المناقشة (Discussion Request)
- **Step 3:** جلسة المناقشة (Discussion Session)
- **Step 4:** الموافقة النهائية (Final Approval)

Completed steps = blue filled circle with checkmark. Current step = pulsing blue circle. Future steps = muted outlined circle.

**Eligibility section** (if thesis not yet registered): White card showing eligibility criteria as a checklist:
- "عدد المقررات المجتازة: 8/10" — green if met, red if not
- "المعدل التراكمي: 3.2" — green ≥ 2.5, red below
- "لا توجد درجات رسوب" — green or red

If all criteria met: green "مستوفٍ لشروط التسجيل" banner + Register button.
If not met: red "غير مستوفٍ لشروط التسجيل" banner, no button.

**Active thesis card** (if registered): white card showing thesis title, supervisor name, registration date.

**Discussion request section** (if in phase 2–4): status-specific card showing current phase, any admin notes, and scheduled date if applicable.

---

## SCREEN 9 — إخلاء الطرف (Clearance)

**Two states:**

**Not eligible:** Large centered illustration (academic building or diploma), text "إخلاء الطرف غير متاح بعد" in 20px, below it "يصبح متاحاً بعد اكتمال إجراءات المناقشة والموافقة النهائية" in 14px muted. A progress indicator showing current discussion phase.

**Eligible:**
- Green success banner "تمت الموافقة على إخلاء طرفك"
- Clearance details card: student name, enrollment number, specialization, graduation date, supervisor name
- Two action buttons side by side: "طباعة / تنزيل PDF" (primary blue) and "مشاركة" (outlined)

---

## SCREEN 10 — الفرص الاستثنائية (Exceptional Requests)

**Screen title** "الفرص الاستثنائية".

**Info banner:** Blue info card explaining "الفرصة الاستثنائية تتيح لك التقدم لفرصة إضافية في مقررات رسبت فيها".

**Request form:**
- Course selector: shows only courses the student has failed
- Opportunity type: segmented control "فرصة رسوب عادي" / "فرصة حضور"
- Reason textarea
- Submit button

**Requests list below:** History of past exceptional requests with course name, type chip, status badge, and date.

---

## SCREEN 11 — حسابي (My Account / Profile)

**Top section:** Avatar circle (initials-based, primary blue background), student's Arabic full name in 22px bold, specialization below in 16px muted, enrollment number in 14px muted.

**Info cards:** Two-column grid of labeled info chips — admission year, student status badge (colored), transfer status.

**Settings list:** Section rows with icon + Arabic label + chevron: "الإشعارات", "تغيير كلمة المرور", "تواصل مع الإدارة", "تسجيل الخروج" (last item in danger red).

---

## ADDITIONAL DESIGN NOTES

**Cards:** rounded-2xl (16px radius), white background, 1px `#E2E8F0` border, 4px shadow (very subtle — shadow-sm level only).

**Buttons:** rounded-xl, 14px Noto Kufi Arabic medium weight, 48pt minimum height, full-width in forms.

**Status badges / pills:** rounded-full, 12px font, 4px vertical padding, 10px horizontal padding. Color-coded by status.

**Empty states:** centered illustration + Arabic heading + muted subtext + optional action button.

**Loading skeletons:** pulse animation on placeholder rectangles matching the shape of cards/list items — never a full-screen spinner.

**Inputs:** white background, 1px border (muted), 48pt height, 16px Arabic placeholder text, error state shows red border + error text directly below the field in 12px red.

**Modals / bottom sheets:** slide up from bottom, rounded top corners (24px), dark scrim behind, drag handle at top center, close button (X) at top left.

**Do not use emoji anywhere in the UI.**
