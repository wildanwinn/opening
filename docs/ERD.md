# ERD Database Magangsto

Tabel data dipisah per sumber file, lalu direlasikan lewat **kunci bisnis**:

| Relasi | Kunci |
|---|---|
| BIMA ↔ Data Monitoring ↔ KPRO | `bima_orders.workorder` = `monitorings.wonum` = `kpro_orders.wonum` |
| BIMA ↔ TSEL | `tsel_orders.workorder` → `bima_orders.workorder` (foreign key) |
| Order ↔ ODP | `monitorings.odp_name` = `odps.nama_odp` (juga `kpro_orders.odp_name`) |
| STO | `sto.kode_sto` = `monitorings.sto` = `odps.sto`; `users.sto_id` → `sto.id` |

Catatan:
- Tabel `monitorings` punya kolom `odp_id` (FK ke `odps.id`, nullable). Diisi otomatis dari kecocokan `monitorings.odp_name = odps.nama_odp` setiap selesai upload ODP / Data Master / KPRO.
- Kolom ODP di tabel `odps` bernama **nama_odp** (bukan `odp_name`) supaya berbeda dengan header file KPRO / Data Master. File ODP dari sumber memakai header `ODP_NAME`; saat upload ODP header itu otomatis dibaca sebagai `nama_odp`. File Master/KPRO tetap ditolak karena puluhan header lainnya bukan header ODP.
- `tsel_orders` hanya berisi WO yang **Order ID TSEL**-nya terisi di BIMA. WO yang tidak ada di tabel ini = non TSEL.
- Status yang tampil di Data Monitoring diambil dari `bima_orders.status`; WO yang belum ada di BIMA memakai `monitorings.status`.
- Setiap upload punya primary key: `workorder` (BIMA), `wonum` (KPRO dan Data Master), `nama_odp` (ODP). Upload ulang memperbarui baris yang kuncinya sama.

```mermaid
erDiagram
    STO ||--o{ USERS : "sto_id"
    STO ||--o{ ODPS : "kode_sto = sto"
    STO ||--o{ MONITORINGS : "kode_sto = sto"
    ODPS ||--o{ MONITORINGS : "nama_odp = odp_name"
    ODPS ||--o{ KPRO_ORDERS : "nama_odp = odp_name"
    BIMA_ORDERS ||--o| TSEL_ORDERS : "workorder (FK)"
    BIMA_ORDERS ||--o| MONITORINGS : "workorder = wonum"
    KPRO_ORDERS ||--o| MONITORINGS : "wonum"
    UPLOAD_BATCHES ||--o{ MONITORINGS : "upload_batch_id"
    UPLOAD_BATCHES ||--o{ BIMA_ORDERS : "upload_batch_id"
    UPLOAD_BATCHES ||--o{ KPRO_ORDERS : "upload_batch_id"
    USERS ||--o{ UPLOAD_BATCHES : "user_id"
    MONITORINGS ||--o{ ORDER_EDIT_LOGS : "monitoring_id"
    USERS ||--o{ ORDER_EDIT_LOGS : "user_id"
    MONITORINGS ||--o{ PEKERJAAN_MATERIALS : "monitoring_id"
    MATERIALS ||--o{ PEKERJAAN_MATERIALS : "material_id"
    MONITORINGS ||--o{ PEKERJAAN_FOTOS : "monitoring_id"
    USERS ||--o{ PEKERJAAN_FOTOS : "user_id"

    USERS {
        bigint id PK
        string nik UK
        string nama
        string kode_teknisi
        enum role "admin hd teknisi edp tsel tif service_area oc3"
        bigint sto_id FK
    }
    STO {
        bigint id PK
        string kode_sto UK
        string nama_sto
        string wilayah
    }
    ODPS {
        bigint id PK
        string nama_odp UK "kunci upload ODP"
        string sto
        decimal latitude
        decimal longitude
        string regional
        string witel
        string datel
        int avai
        int used
        string status
    }
    MONITORINGS {
        bigint id PK
        string wonum UK "= BIMA.workorder = KPRO.wonum"
        string sto
        string odp_name "-> ODPS.nama_odp"
        string customer_name
        string package_name
        string chief_code "teknisi"
        string chief_name "teknisi"
        string status
        datetime statusdate
        string tahap "pre jalan selesai"
        text catatan_teknisi
        bigint upload_batch_id FK
    }
    BIMA_ORDERS {
        string workorder PK "kunci upload BIMA"
        string status "dipakai Data Monitoring"
        datetime status_date
        string workzone
        string crm_order_type
        string customer_name
        string owner_group
        bigint upload_batch_id FK
    }
    TSEL_ORDERS {
        bigint id PK
        string workorder FK,UK
        string order_id_tsel
        string channel_id_tsel
    }
    KPRO_ORDERS {
        string wonum PK "kunci upload KPRO"
        string sto
        string odp_name
        string chief_name
        string status
        datetime statusdate
        bigint upload_batch_id FK
    }
    UPLOAD_BATCHES {
        bigint id PK
        string jenis "master odp kpro bima"
        string nama_file
        string status
        int total_baris
        bigint user_id FK
    }
    ORDER_EDIT_LOGS {
        bigint id PK
        bigint monitoring_id FK
        string kolom
        text nilai_lama
        text nilai_baru
        bigint user_id FK
    }
    MATERIALS {
        bigint id PK
        string nama UK
        string satuan
    }
    PEKERJAAN_MATERIALS {
        bigint id PK
        bigint monitoring_id FK
        bigint material_id FK
        int qty
    }
    PEKERJAAN_FOTOS {
        bigint id PK
        bigint monitoring_id FK
        bigint user_id FK
        string jenis "material sesudah"
        string path
    }
```
