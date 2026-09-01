# Teacher Identity Provisioning

## Purpose

Attach one authenticated account and active `teacher` school membership to one
existing active teacher profile in one existing school. This is a controlled
pilot bootstrap command, not generic user management.

## Prerequisites

- Existing school ID.
- Existing active teacher profile ID belonging to that school.
- A unique account email and password of at least eight characters.

Create the teacher profile first through the existing school-admin teacher
workspace; this command intentionally does not create or edit profiles.

## Usage

Interactive execution hides the password:

```bash
php artisan edubangla:provision-teacher-identity \
  --school-id=42 --teacher-id=17
```

Controlled non-interactive execution:

```bash
php artisan edubangla:provision-teacher-identity \
  --school-id=42 --teacher-id=17 \
  --name="Teacher Name" --email=teacher@example.test \
  --password='<secure-value>' --force
```

## Safety

The command resolves both school and teacher profile server-side, creates one
hashed account, one active `teacher` membership, and links the profile in one
transaction. It never uses browser/session tenant context or modifies another
school. A failed membership step rolls back a newly created account and the
profile link.

Existing emails fail closed by default. `--existing-user` is available only for
an explicitly compatible account with no other-school membership; identity data
and passwords are never overwritten. Exact compatible reruns resolve without
duplicates.

## Explicit non-scope

This command does not create schools, teacher profiles, student identities,
student memberships, parent identities, catalog records, assignments,
enrollments, or public registration.
