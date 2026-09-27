# Cavernas

Cavernas is an original Laravel forum role-playing world focused on character-led stories, four allegiances, territory-based energy, crickets, breeding, and staff moderation.

## Current platform

- Laravel 12 and PHP 8.2+.
- Blade, Vite, and Tailwind CSS.
- Account approval and staff review.
- Forum boards, IC/OOC posts, character records, cricket ledger, shop, inventory, lifecycle, breeding, and moderation reports.

## Start here

See [docs/SETUP.md](docs/SETUP.md) for local installation, migrations, scheduler, storage, testing, and production release requirements.

Useful commands:

```powershell
php artisan migrate --seed
php artisan test
npm run build
```

## Work log / invoice

| Date | Scope | Notes |
| --- | --- | --- |
| 2026-09-10 | Account approval and member onboarding | Added approval gating, account display, and the initial user signup flow. |
| 2026-09-11 | Profile and user identity fields | Added bio, pronouns, and profile metadata needed for member identity and character linking. |
| 2026-09-12 | Notification foundation | Created the database notification system for approvals, transfers, and updates. |
| 2026-09-13 | Forum base structure | Added the boards and threads scaffolding used for IC and OOC.roleplay. |
| 2026-09-15 | Character and currency infrastructure | Created characters plus the cricket ledger and core ownership data. |
| 2026-09-16 | Shop and inventory tools | Added purchasable items and the inventory flow for one-time use effects. |
| 2026-09-20 | Breeding and lifecycle systems | Added pregnancy, litter creation, and the lifecycle progression model. |
| 2026-09-21 | Mate and birth logic | Added mate request flow and the supporting birth-time updates. |
| 2026-09-23 | Moderation and safety layer | Added moderation reports and the staff review process. |
| 2026-09-24 | Character health and rare traits | Added health status and trait profile handling for ailments and rare markings. |
| 2026-09-26 | Role, audit, and profile polish | Finalized role normalization, audit logging, and the latest special-profile additions. |

Do not commit credentials, production secrets, or content/assets copied from reference sites.
