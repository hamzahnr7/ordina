# User Stories & Scope

Reference: Project Brief §1 (Alur utama, Peran & SoD), §2 (Requirement Wajib).

## Format
`Sebagai <role>, saya ingin <aksi>, agar <tujuan>.`

## Admin
- [x] Sebagai Admin, saya ingin mengelola akun Sales/Warehouse Staff, agar hanya orang yang berwenang bisa memakai sistem. (USR-01)
- [x] Sebagai Admin, saya ingin mengelola master data produk/kategori/gudang/supplier/customer, agar data transaksi konsisten. (PRD-01, WH-01)
- [ ] Sebagai Admin, saya ingin menyetujui/menolak Sales Order, agar tidak ada transaksi yang lolos tanpa review. (SO-01)
- [ ] Sebagai Admin, saya ingin melihat dashboard nilai inventori & reorder alert, agar bisa mengambil keputusan pembelian. (DASH-01)

## Sales
- [ ] Sebagai Sales, saya ingin membuat & mengajukan Sales Order, agar pesanan customer bisa diproses. (SO-01)
- [ ] Sebagai Sales, saya ingin melihat ringkasan order milik saya, agar saya tahu status tiap order. (DASH-01)

## Warehouse Staff
- [ ] Sebagai Warehouse Staff, saya ingin memproses goods receipt dari PO, agar stok bertambah sesuai barang fisik yang datang. (PO-01)
- [ ] Sebagai Warehouse Staff, saya ingin memproses goods issue dari SO yang Approved, agar stok berkurang secara aman tanpa oversell. (SO-01, ARCH-02)
- [ ] Sebagai Warehouse Staff, saya ingin mengusulkan Purchase Order saat stok rendah, agar proses restock tidak menunggu Admin selalu online. (PO-01)

## Out of scope
See brief §4.3 (microservices, message queue, cloud deployment, CI/CD, Kubernetes, real-time notification, mobile app, automated cron, automated E2E test).

## Backlog (fill in as work progresses)
| # | Story | Requirement ID | Status |
|---|-------|-----------------|--------|
| 1 | Login/logout | AUTH-01/02 | Done |
| 2 | User management | USR-01 | Done |
| 3 | Product/category CRUD + image upload | PRD-01 | Done |
| 4 | Warehouse CRUD + per-product multi-location stock view | WH-01 | Done |
| 4b | Supplier/Customer CRUD | §1.3 master data | Done |
| 5 | Purchase Order + goods receipt | PO-01 | Not started |
| 6 | Sales Order + approval + goods issue | SO-01 | Not started |
| 7 | Search/filter/sort/pagination | FIND-01 | Done for Product; PO/SO pending those slices |
| 8 | Dashboard per role | DASH-01 | Nav only - aggregation numbers pending PO/SO data |
| 9 | CSV report | REPORT-01 | Not started |
| 10 | JSON API endpoint | API-01 | Done |
| 11 | Low-stock scheduled script | JOB-01 | Scaffolded (untested against real data yet) |
| 12 | Role/Permission/Menu architecture | ARCH-01 (SoD) | Done |
