# AI Usage Log

Per brief §6.2 (DISCLOSE / REVIEW / VERIFY / TEST).

| Date | Tool | Purpose | Prompt summary (sanitized) | Output used? | Verification performed |
|------|------|---------|------------------------------|----------------|--------------------------|
| 2026-09-03 | Claude Code | Scaffold initial project structure (Docker, folder layout, layered-architecture skeleton, DB schema draft, docs templates) from the uploaded Project Brief PDF | "Init project per brief, generate template structure; use Redis to make dev easier" | Yes - structure kept, to be filled in with real business logic by hand | Manually reviewed folder layout and docker-compose against brief §4.1/§5.1; schema checked against §1.3 entity table; no code has been run yet - functional verification pending first `docker compose up` |

## Notes
- All architecture decisions in `docs/architecture/adr-*.md` must be
  re-explained by the participant unaided during technical defense,
  regardless of how they were drafted.
- Update this table every time an AI tool materially contributes code,
  docs, or design decisions - including "no AI used this session" entries
  if applicable, per the brief's requirement to disclose even non-use.
