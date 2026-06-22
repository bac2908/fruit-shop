# Project Map - FE / BE / DB

Thu muc nay dung de mo nhanh cac phan cua du an theo 3 lop:

```text
PROJECT_MAP/
    FE/
    BE/
    DB/
```

Luu y: day la ban do file, khong phai source Laravel moi. Cac link trong tung README tro ve file goc cua du an de khi sua thi app van chay dung.

## Mo nhanh

- [FE](./FE/README.md): giao dien Blade, layout, storefront, admin UI.
- [BE](./BE/README.md): route, controller, service, model, middleware.
- [DB](./DB/README.md): env database, migration, seeder, cac bang MySQL.

## Flow tong quat

```text
User / Browser
    -> FE Blade
    -> Route Laravel
    -> Controller / Service
    -> Model Eloquent
    -> MySQL database web_traicay
    -> Blade render HTML tra ve browser
```

