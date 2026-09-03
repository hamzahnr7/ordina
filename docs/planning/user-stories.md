# User Stories & Scope

Reference: Project Brief §1 (Alur utama, Peran & SoD), §2 (Requirement Wajib).

## Format
`Sebagai <role>, saya ingin <aksi>, agar <tujuan>.`

## Admin
- [ ] Sebagai Admin, saya ingin mengelola akun Sales/Warehouse Staff, agar hanya orang yang berwenang bisa memakai sistem. (USR-01)
- [ ] Sebagai Admin, saya ingin mengelola master data produk/kategori/gudang/supplier/customer, agar data transaksi konsisten. (PRD-01, WH-01)
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
| 1 | Login/logout | AUTH-01/02 | Not started |
| 2 | User management | USR-01 | Not started |
| 3 | Product/category CRUD | PRD-01 | Not started |
| 4 | Warehouse & multi-location stock | WH-01 | Not started |
| 5 | Purchase Order + goods receipt | PO-01 | Not started |
| 6 | Sales Order + approval + goods issue | SO-01 | Not started |
| 7 | Search/filter/sort/pagination | FIND-01 | Not started |
| 8 | Dashboard per role | DASH-01 | Not started |
| 9 | CSV report | REPORT-01 | Not started |
| 10 | JSON API endpoint | API-01 | Scaffolded |
| 11 | Low-stock scheduled script | JOB-01 | Scaffolded |
