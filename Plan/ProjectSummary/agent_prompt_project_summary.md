# Agent Prompt: Generate Project Summary (Markdown)

---

## Instructions for the Agent

You are a senior software engineer and technical writer. Your task is to produce a **comprehensive, professional Project Summary** in Markdown format for a university internship (KP) project: a **Computer Lab Scheduler System**.

You have access to two sources of information:
1. **The Planning Document** — a previous plan that outlines the system's features PLAN-UI.md and C:\xampp\htdocs\UKRIDA_LabReserve\Plan\PROJECT-HANDOVER.md , recommended tech stack, and architecture.
2. **The Codebase** — the actual source code of the project (all files in the repository/project directory).

Follow the exact steps below. Do not skip any step.

---

## Step 1 — Read and Understand the Plan

Read the planning document thoroughly. Extract and internalize:
- The system's purpose and context (what problem it solves, for whom)
- The full feature list (both basic features from the lecturer and the professional-grade additions)
- The recommended tech stack and architecture
- The intended approval workflow and user roles

---

## Step 2 — Audit the Codebase

Scan every relevant file in the codebase. For each major feature or module described in the plan, determine:
- ✅ **Implemented** — The feature exists and is functional in the code
- 🚧 **Partially Implemented** — The feature exists but is incomplete (note what is missing)
- ❌ **Not Started** — The feature was planned but no code exists for it
- 🔄 **Changed** — The feature was implemented differently from what was planned (note the deviation and the reason if inferable)

Categories to check:
- [ ] Authentication & Role System (Admin / Operator / User)
- [ ] Room & Computer Asset Management (individual unit tracking)
- [ ] Booking Request Flow (Draft → Submitted → Under Review → Approved/Rejected → Completed)
- [ ] Visual Calendar View (weekly/monthly)
- [ ] Recurring Booking
- [ ] Buffer Time Between Sessions
- [ ] Waitlist / Queue System
- [ ] Email Notification System
- [ ] In-App Notifications
- [ ] Dashboard & Analytics (usage stats, exports)
- [ ] Audit Log
- [ ] Google Calendar Integration (OAuth 2.0)
- [ ] Session Timeout & Security (bcrypt, rate limiting, JWT)
- [ ] Frontend Stack (React + Tailwind + shadcn/ui or Ant Design)
- [ ] Backend Stack (Laravel or Node/Express + Prisma)
- [ ] Database Schema (PostgreSQL/MySQL)
- [ ] Docker / Deployment Setup
- [ ] Unit Tests & API Tests

---

## Step 3 — Write the Project Summary in Markdown

Produce a single Markdown file (`PROJECT_SUMMARY.md`) with the following structure. Use clear headers, tables where appropriate, and short but complete descriptions.

```
# Project Summary: Computer Lab Scheduler System

## 1. Project Definition
[What is this system? One clear paragraph. Define the domain, the scope, and the type of application.]

## 2. Project Purpose
[Why was this built? Who are the users? What problem does it solve in a real campus environment?]

## 3. System Architecture Overview
[Briefly describe the chosen architecture (REST API, separated frontend/backend, etc.) and the tech stack that was actually used — not just what was planned.]

## 4. User Roles
[Table: Role | Permissions | Notes]

## 5. Development Phases & Feature Status

### Phase 1 — Core Infrastructure
[Describe phase, then list features with ✅ 🚧 ❌ 🔄 status and short notes.]

### Phase 2 — Booking & Scheduling System
[Same format]

### Phase 3 — Notifications & Communication
[Same format]

### Phase 4 — Admin Tools, Analytics & Reporting
[Same format]

### Phase 5 — Security, Testing & Deployment
[Same format]

## 6. Changes From the Original Plan
[A dedicated section listing every point where the implementation deviated from the plan. For each:
- What was planned
- What was built instead (or omitted)
- Inferred reason (technical constraint, scope reduction, better approach, etc.)]

## 7. Known Gaps & Recommendations
[Features that were not implemented but are important for real-world use. Prioritized list with brief rationale.]

## 8. How to Run the Project
[Quick-start instructions: prerequisites, install commands, environment variables, run commands. Keep it concise.]
```

---

## Output Requirements

- Format: **Markdown only** — no HTML, no code fences around the whole document
- Tone: **Professional and factual** — suitable for a KP internship report appendix
- Length: **As long as needed to be complete** — do not summarize the status table into vague statements
- Language: **English** (unless the user specifies Bahasa Indonesia)
- Every feature status entry must reference a specific file, module, or route in the codebase to justify its status label — do not guess

---

## Final Check Before Outputting

Before writing the output, ask yourself:
1. Have I read every source file, not just the main entry point?
2. Is every feature in the plan accounted for with a status?
3. Is every deviation from the plan explained — not just listed?
4. Would a person unfamiliar with this project understand the summary without needing to read the code?

If all four answers are yes, write the output.
