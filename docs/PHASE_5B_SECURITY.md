# Phase 5B Security

Student identity is resolved from authenticated user to tenant-scoped student. Start, answer and submit actions verify enrollment, schedule scope, ownership, lifecycle and server expiry. Client timestamps, remaining seconds, marks and question content are ignored. Expired attempts remain `in_progress` but are mutation-blocked (reject-only policy); a future scheduler can finalize them without trusting the client. Teachers/admins cannot impersonate students.
