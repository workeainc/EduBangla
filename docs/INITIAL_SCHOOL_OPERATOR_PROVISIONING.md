# Initial School Operator Provisioning

## Purpose

Provision exactly one initial school administrator for one existing school
through a controlled shell operation. This is not public registration or a
general user-management facility.

## Prerequisites

- An existing school ID.
- Authorized operator access to the application environment.
- A unique operator email and a password of at least eight characters.

## Usage

Interactive (password is hidden):

```bash
php artisan edubangla:provision-initial-operator --school-id=42
```

Non-interactive controlled execution (avoid shell history for passwords):

```bash
php artisan edubangla:provision-initial-operator \
  --school-id=42 --name="School Admin" --email=admin@example.test \
  --password='<secure-value>' --force
```

The command resolves the school server-side, creates one `User`, and creates
one active `school-admin` `SchoolUser` membership. It prints no password.

## Existing accounts and reruns

An existing email fails closed by default. Add `--existing-user` only when the
account is explicitly known to be compatible and has no other school
membership. An existing active school-admin membership is resolved without
changing identity data or passwords. Incompatible memberships and accounts
belonging to another school are rejected.

## Safety and rollback

User and membership creation run in one transaction. A failed membership step
rolls back a newly created user; pre-existing compatible records are unchanged.
The operation targets one school and never uses browser/session tenant state.

## Explicit non-scope

This command does not provision a school, catalog, teachers, students,
guardians, enrollments, assignments, portal identities, parent accounts, or
public registration.
