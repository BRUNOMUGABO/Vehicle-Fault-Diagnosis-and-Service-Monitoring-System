# Vehicle Fault Diagnosis and Service Monitoring System

## Run with Docker

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and **running**

---

### First-time setup

**Step 1 — Export the database from XAMPP**

While XAMPP is still running, open phpMyAdmin at `http://localhost/phpmyadmin`, select the `vfdsms` database, click **Export → Go**, and save the file as `vfdsms.sql` in this folder.

Or if you have `mysqldump` available:
```sh
mysqldump -u root --password="" vfdsms > vfdsms.sql
```

**Step 2 — Start Docker Desktop**, then run:

```sh
docker compose up --build -d
```

The `vfdsms.sql` file is automatically imported on the first start (when the `db_data` volume is empty).

**Step 3 — Open the app:**

- App: http://localhost:8080/Vehicles%20Fault%20Diagnosis%20and%20Service%20Monitoring%20System/home.php
- phpMyAdmin: http://localhost:8081

---

### Daily use

```sh
# Start
docker compose up -d

# Stop (keeps database)
docker compose down

# Rebuild after changing PHP files
docker compose up --build -d
```

---

### Notes
- The database is stored in the `db_data` Docker volume and persists across restarts.
- Do **not** run `docker compose down -v` unless you want to delete the database.
- The `vfdsms.sql` file is only imported once (on first start with an empty volume). To re-import, run:
  ```sh
  docker compose down -v
  docker compose up --build -d
  ```
- Default credentials (from `.env.example`):
  - DB user: `vfdsms_user` / `vfdsms_local_password`
  - DB root: `vfdsms_local_root_password`
  - phpMyAdmin login: root / `vfdsms_local_root_password`
