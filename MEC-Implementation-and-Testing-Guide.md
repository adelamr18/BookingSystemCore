# MEC Booking System - Implementation & Testing Guide

**Purpose:** Map each requirement from the original *CORE Booking System - MEC Project Requirements Specification* to the current implementation and provide clear UAT steps.

**Original document:** `CORE Booking System - MEC Project Requirements Specification.pdf`  
**Updated against codebase:** March 12, 2026  
**Scope note:** This guide stays aligned to the MEC requirements document. Recent CORE branding and login UI polish are outside the PDF scope and are not detailed here.

Items explicitly marked in the PDF as **Requires Further Discussion** remain excluded from the implementation checklist, except the **roles/access-control** section, which is implemented and documented below.

---

## Test setup

Use this baseline before running the checks:

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
```

Seeded admin login:

```text
Email: admin@example.com
Password: admin123
```

Legend used in the tables:

- `Implemented` = aligned with the current app
- `Partial` = present, but with a limitation relative to the PDF
- `Not implemented` = intentionally deferred because it requires clarification

---

## 1. Participant Visit Workflow and MEC Clinics

| Original PDF requirement | Current implementation | Status | How to test |
|---|---|---|---|
| Participant can have multiple appointments across stages. | `spid` is not unique; each appointment stores its own `visit_stage`. | Implemented | Book two appointments with the same SPID and different dates/stages. In **All Appointments**, confirm both records exist for the same SPID. |
| Three stages: First Visit, Second Visit, Third Visit. | Booking form stores `first_visit`, `second_visit`, or `third_visit`. | Implemented | On the frontend booking flow, complete bookings using different stage values and confirm the stage appears in the appointment modal/report. |
| Three MEC clinics: MEC1, MEC2, MEC3. | Seeder creates MEC1, MEC2, MEC3 as branches. | Implemented | After `migrate:fresh --seed`, confirm **Branches (MEC)** shows MEC1, MEC2, MEC3. |
| MEC appointment takes approximately 1 hour and covers five sequential tests. | The seeded service text says the MEC examination is approximately 1 hour with five tests. Actual slot duration is still driven by employee `slot_duration`, and the seeded admin employee defaults to 30 minutes. | Partial | Select a branch and verify the service description. Then check the available times: slot length follows employee configuration, not a fixed 60-minute rule. |

---

## 2. Branch Structure Instead of Categories

| Original PDF requirement | Current implementation | Status | How to test |
|---|---|---|---|
| Replace Category with Branch. | User-facing MEC flow now uses **Branch** / **Branches (MEC)** in the main admin and frontend booking flow. | Implemented | Confirm the sidebar menu says **Branches (MEC)** and the frontend first step says **Select a Branch (MEC)**. |
| Flow should start with Branch selection. | Public booking route now loads services from `/branches/{branch}/services`. | Implemented | Open `/`, select a branch, and confirm services load after the branch selection step. |
| Branch example should be MEC1 / MEC2 / MEC3. | Seed data and UI use MEC1, MEC2, MEC3. | Implemented | Verify the branch cards or branch list after seeding. |

Note: some legacy internal file/class names still use `Category` because the underlying model/table was reused. The user-facing workflow is branch-based.

---

## 3. Branch Data Requirements

| Original PDF requirement | Current implementation | Status | How to test |
|---|---|---|---|
| Each branch stores Branch Name, City, Address, Map link. | Branch create/edit forms include `title`, `city`, `address`, `map_link`. | Implemented | Go to **Branches (MEC)** -> **Add New** or **Edit** and confirm those fields are available and saved. |
| Exact branch location at booking time must be preserved in appointment history. | Appointments store `branch_id`, `branch_address_snapshot`, `branch_map_link_snapshot`. | Implemented | 1. Edit MEC1 and set an address/map link. 2. Book an appointment. 3. Change MEC1 address/map link and book another appointment. 4. Verify snapshots in DB or Tinker. |
| Map integration / map API. | Storage only. No Google Maps API integration or visualization layer. | Not implemented | Skip. |
| Appointment counts on map / KML visualization. | Not built. | Not implemented | Skip. |

Optional technical verification for branch snapshots:

```bash
php artisan tinker --execute="App\\Models\\Appointment::select('spid','branch_address_snapshot','branch_map_link_snapshot')->latest()->take(2)->get()->toArray();"
```

---

## 4. Removal of Payment Functionality

| Original PDF requirement | Current implementation | Status | How to test |
|---|---|---|---|
| Remove service pricing fields and payment workflow. | Service create/edit screens no longer show `price` or `sale_price`. The schema no longer keeps active service price fields in the MEC build. | Implemented | Open **Services** -> **Add New** or **Edit** and confirm there are no price inputs. |
| Remove appointment amount and payment references. | New/updated schema removes `amount`; appointment UI no longer shows amount. | Implemented | Open **All Appointments** and the appointment modal; confirm there is no amount field. |
| Remove commercial payment behavior. | Frontend booking flow has no price display, no amount summary, and no payment step. | Implemented | Complete a booking from `/` and confirm the summary and success modal do not mention price or payment. |
| Remove old `Pending payment` behavior. | New UI uses `Pending`, `Confirmed`, `Cancelled`, `Completed`, `On Hold`, `No Show`. | Implemented | Open any appointment modal and confirm `Pending payment` is not available. |

Optional technical verification:

```bash
php artisan tinker --execute="dump(Schema::hasColumn('appointments','amount'), Schema::hasColumn('services','price'), Schema::hasColumn('services','sale_price'));"
```

Expected result:

```text
false
false
false
```

---

## 5. Email Collection Removal

| Original PDF requirement | Current implementation | Status | How to test |
|---|---|---|---|
| Remove participant email from booking. | Frontend booking form no longer asks for email. | Implemented | Go through the booking flow and confirm there is no email field in participant information. |
| Remove email from appointment storage. | Active MEC schema removes appointment email storage. | Implemented | Verify via UI or Tinker that appointment email is not part of the record structure anymore. |
| Communication should not depend on participant email. | Participant-facing email listeners were disabled; no participant email is sent on booking/status update. SMS is still pending and remains outside the implemented scope. | Implemented | Trigger a booking and confirm there is no participant email workflow. |

Optional technical verification:

```bash
php artisan tinker --execute="dump(Schema::hasColumn('appointments','email'));"
```

Expected result:

```text
false
```

---

## 6. Participant Information Fields

| Original PDF requirement | Current implementation | Status | How to test |
|---|---|---|---|
| SPID must be numeric and exactly 10 digits. | Backend validates `digits:10`; stored in `appointments.spid`. | Implemented | Try 9 digits or 11 digits and confirm validation fails. Then book with `1234567890` and confirm it appears in the appointment modal/report. |
| Sample Person Name required. | Stored in `sample_person_name` and mirrored into `name` for legacy compatibility. | Implemented | Leave blank and confirm validation. Then save and confirm it appears in appointment listings. |
| Mobile Number should be valid. | Field exists and is required, but backend currently validates it as a string up to 20 chars rather than a strict numeric/phone pattern. | Partial | Leave blank and confirm validation fails. Enter a non-phone string and note that current backend validation is permissive. |
| Interviewer ID required. | Stored in `interviewer_id`. | Implemented | Book with `inter20` and confirm it appears in the appointment modal and branch report. |
| Supervisor ID required. | Stored in `supervisor_id`. | Implemented | Book with `sv01` and confirm it appears in the appointment modal and branch report. |

---

## 7. Visit Stage Handling

| Original PDF requirement | Current implementation | Status | How to test |
|---|---|---|---|
| First Visit (Household), Second Visit (MEC), Third Visit (Follow-up MEC). | Booking form exposes all three stage options and stores the selected value. | Implemented | Book one appointment for each stage and verify the appointment modal and branch report reflect the stored stage where exposed. |

Note: the branch report does not currently display the visit stage column; the appointment modal and appointment list do.

---

## 8. Branch Reporting Requirement

| Original PDF requirement | Current implementation | Status | How to test |
|---|---|---|---|
| Each branch must generate its own report. | Branch list includes a **Report** action that loads appointments filtered by branch. | Implemented | Create appointments in MEC1 and MEC2. Open MEC1 report and confirm only MEC1 appointments are shown. |
| Report fields: Branch, Appointment Date, Appointment Time, SPID, Sample Person Name, Mobile Number, Interviewer ID, Supervisor ID, Status. | Branch report contains exactly these columns. | Implemented | Open any branch report and confirm all listed columns are present. |
| Print-friendly reporting. | Report page includes a **Print** button and print CSS hides sidebar/navbar/footer. | Implemented | Open a branch report and use **Print**. Confirm the report is printable without admin chrome. |

---

## 9. User Roles and Access Control

| Original PDF requirement | Current implementation | Status | How to test |
|---|---|---|---|
| Three assignable roles only: Subscriber/Admin, View-Only, Employee. | User create/edit dropdown exposes exactly these three labels. Internal `admin` remains for the seeded super-admin but is not an assignable UI option. | Implemented | Open **Users** -> **Add New** and confirm the dropdown contains only `Subscriber/Admin`, `View-Only`, and `Employee`. |
| Subscriber/Admin has full system access. | `subscriber` is granted full permissions and is labeled `Subscriber/Admin` in UI. Seeded `admin` also retains full access. | Implemented | Log in as admin or assign a subscriber user and confirm access to Users, Branches, Services, Appointments, and Settings. |
| Employee can create/view/edit only the appointments they created. | Employee permissions are limited to `appointments.view`, `appointments.create`, `appointments.edit`. Appointment list/dashboard are filtered by `created_by_id`, and status updates are rejected for appointments created by others. | Implemented | Log in as an employee, create an appointment, then compare against an appointment created by another user. Confirm the employee only sees and edits their own records. |
| Employee must not manage branches, services, users, or settings. | Route protection is per-action, not a broad OR check. Employee cannot access create/edit/delete screens outside appointments. | Implemented | As an employee, try direct URLs like `/category/create`, `/service/create`, `/user/create`, `/settings`. Access should be denied. |
| View-Only can review data but cannot modify it. | `view_only` has read-only permissions. The appointment modal hides the update button, and backend status updates are blocked. | Implemented | Log in as a view-only user. Open an appointment and confirm there is no **Update Status** button. Try posting a status update and confirm rejection. |

Important current behavior:

- Appointment ownership for employee restrictions is based on `created_by_id`.
- If an admin creates an appointment on behalf of an employee, that appointment is still treated as admin-created, not employee-created.

---

## 10. Items Not Implemented Because the PDF Marked Them for Further Clarification

These should **not** be treated as missing defects in the current build.

| Original PDF area | Status |
|---|---|
| Google Maps API integration | Not implemented |
| KML map visualization of appointments | Not implemented |
| SMS integration and reminder schedule | Not implemented |
| Automated status tracking / auto no-show | Not implemented |
| Multi-capacity slot rules / max 3 participants per slot | Not implemented |
| Controlled overbooking | Not implemented |
| Data import from Excel or other sources | Not implemented |
| Re-entry handling workflow | Not implemented |
| Incorrect MEC booking correction workflow | Not implemented |
| Final project timeline clarification | Not implemented |

---

## Quick UAT Checklist

- [ ] Run `php artisan migrate:fresh --seed`.
- [ ] Log in with `admin@example.com / admin123`.
- [ ] Confirm **Branches (MEC)** contains MEC1, MEC2, MEC3.
- [ ] Confirm branch create/edit screens have City, Address, and Map link fields.
- [ ] On `/`, verify step 1 is branch-based and step 5 contains SPID, Sample Person Name, Mobile Number, Visit Stage, Interviewer ID, Supervisor ID, and Notes.
- [ ] Confirm there is no participant email field and no pricing/payment display in the booking flow.
- [ ] Complete a booking and confirm the success modal shows booking reference details without amount/payment content.
- [ ] In **All Appointments**, confirm SPID, Branch, Visit, and modal-only participant metadata are present.
- [ ] Open an appointment modal and confirm status options do not include `Pending payment`.
- [ ] Open a branch report and confirm it contains only appointments for that branch.
- [ ] Create a `View-Only` user and confirm they cannot update status.
- [ ] Create an `Employee` user and confirm they only see/edit appointments they personally created.
- [ ] Optionally verify branch snapshot fields in DB after moving a branch location.
- [ ] Note current limitation: mobile validation is basic, and 1-hour duration is described in content but not enforced as a fixed slot rule.

---

## Converting This Guide to PDF

- Browser/preview print to PDF
- VS Code Markdown PDF extension
- Pandoc:

```bash
pandoc MEC-Implementation-and-Testing-Guide.md -o MEC-Implementation-and-Testing-Guide.pdf
```

---

*This guide reflects the current codebase state relative to the MEC requirements document and intentionally excludes items the PDF marked for further clarification.*
