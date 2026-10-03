# Customer Follow-up Module

## Objective

Implement a customer follow-up module in this standalone assessment application. Do not use code from another private project.

Recommended timebox: 12 hours for backend, or 16 hours when delivering the optional Twig UI.

## Follow-up data

Each follow-up must include:

- customer
- creator
- title
- description
- priority: `low`, `normal`, or `high`
- status: `open` or `completed`
- due date and time
- completion date and time when completed
- creation and modification timestamps

Choose appropriate SQL column types, constraints, foreign keys, and indexes. Explain material decisions in `DECISIONS.md`.

## Required behavior

1. Create a follow-up for a visible customer.
2. List follow-ups with pagination.
3. View one follow-up.
4. Edit an open follow-up.
5. Complete and reopen a follow-up.
6. Filter by customer, status, priority, and due-date range.
7. Record status changes in a history table.
8. Validate all input and return useful HTTP error responses.
9. Prevent users from reading or changing follow-ups for customers they cannot access.
10. Add meaningful unit and integration tests.

An `admin` can access all customers. An `agent` can access only customers whose `assigned_user_id` matches that agent. Returning `404` instead of revealing that an inaccessible record exists is preferred.

## Required endpoints

```text
POST /api/followups
GET  /api/followups
GET  /api/followups/{id}
PUT  /api/followups/{id}
POST /api/followups/{id}/complete
POST /api/followups/{id}/reopen
```

The fake current user is supplied through the `X-User-Id` request header. Do not spend assessment time building authentication.

## Minimum test scenarios

- create a valid follow-up
- reject an empty title
- reject an invalid due date
- complete and reopen a follow-up
- deny access to another agent's customer
- filter by status and due date
- preserve history for status transitions

## Deliverables

- working implementation and migrations
- automated tests runnable through `composer test`
- `DECISIONS.md` describing assumptions, design choices, commands run, and future improvements
- small, meaningful Git commits
- a pull request describing the result

Do not add a framework or runtime dependency without explaining the need. Do not commit `.env`, generated dependencies, credentials, or database files.

## Optional full-stack extension

Add Twig pages for listing, filtering, creating, editing, completing, and reopening follow-ups. Clearly distinguish overdue open items. Keep the interface usable on narrow screens.

## Review focus

The review prioritizes correctness, server-side authorization, separation of responsibilities, useful tests, database design, query efficiency, and the candidate's ability to explain trade-offs.

