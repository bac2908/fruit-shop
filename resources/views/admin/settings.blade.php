@extends('layouts.admin')

@section('title', 'Cai dat he thong | FruitShop Admin')

@section('head')
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field label {
            font-size: 12px;
            color: #5f7368;
            font-weight: 600;
        }

        .input,
        .textarea,
        .select {
            width: 100%;
            border: 1px solid #d6dfd4;
            border-radius: 11px;
            padding: 10px 11px;
            font-size: 13px;
            font-family: inherit;
            background: #fff;
            color: #173425;
        }

        .textarea {
            min-height: 92px;
            resize: vertical;
        }

        .switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #dde7db;
            border-radius: 12px;
            padding: 11px 12px;
            background: #fff;
            margin-bottom: 8px;
        }

        .switch b {
            font-size: 13px;
        }

        .switch span {
            font-size: 12px;
            color: #66796e;
            display: block;
            margin-top: 4px;
        }

        .toggle {
            width: 42px;
            height: 24px;
            border-radius: 999px;
            background: #d8e5d9;
            position: relative;
        }

        .toggle::after {
            content: '';
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fff;
            position: absolute;
            left: 3px;
            top: 3px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle.on {
            background: #8ed0aa;
        }

        .toggle.on::after {
            left: 21px;
        }

        @media (max-width: 1040px) {
            .settings-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('admin_content')
    <section class="page-head reveal" style="--delay: 0ms;">
        <div>
            <h1 class="page-title">Cai dat he thong</h1>
            <p class="page-subtitle">Quan ly thong tin cua hang, giao van, thanh toan va thong bao.</p>
        </div>
        <button class="btn btn-primary"><i class="ri-save-3-line"></i>Luu thay doi</button>
    </section>

    <section class="settings-grid">
        <article class="panel reveal" style="--delay: 80ms;">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Thong tin cua hang</h2>
                    <p class="panel-sub">Dong bo metadata su dung cho website, invoice va CRM.</p>
                </div>
            </div>

            <form class="form-grid" method="post" action="javascript:void(0)">
                <div class="field">
                    <label>Ten thuong hieu</label>
                    <input class="input" type="text" value="FruitShop">
                </div>
                <div class="field">
                    <label>Hotline</label>
                    <input class="input" type="text" value="0333499426">
                </div>
                <div class="field">
                    <label>Email CSKH</label>
                    <input class="input" type="email" value="bacnguyen2921@gmail.com">
                </div>
                <div class="field">
                    <label>Timezone</label>
                    <select class="select">
                        <option>Asia/Ho_Chi_Minh</option>
                    </select>
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label>Dia chi giao hang mac dinh</label>
                    <textarea class="textarea">74 Trần Thái Tông</textarea>
                </div>
            </form>
        </article>

        <article class="panel reveal" style="--delay: 130ms;">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Tuy chon van hanh</h2>
                    <p class="panel-sub">Bật/tat cac tinh nang thuong dung cho ecommerce.</p>
                </div>
            </div>

            <div class="switch">
                <div>
                    <b>Cho phep COD</b>
                    <span>Khach co the chon thanh toan khi nhan hang</span>
                </div>
                <div class="toggle on"></div>
            </div>

            <div class="switch">
                <div>
                    <b>Tu dong gui email xac nhan don</b>
                    <span>Kich hoat sau khi don duoc tao thanh cong</span>
                </div>
                <div class="toggle on"></div>
            </div>

            <div class="switch">
                <div>
                    <b>Canh bao ton kho thap</b>
                    <span>Thong bao khi ton kho duoi nguong</span>
                </div>
                <div class="toggle on"></div>
            </div>

            <div class="switch">
                <div>
                    <b>Che do bao tri storefront</b>
                    <span>Tam an website de cap nhat he thong</span>
                </div>
                <div class="toggle"></div>
            </div>

            <div class="hero-note" style="margin-top: 10px; margin-bottom: 0; border: 1px dashed #d9e6d8; border-radius: 11px; padding: 10px; background: #f8fbf7; color: #4e6456; font-size: 13px;">
                Form setting da dung theo bo cuc production. Buoc BE se map config vao DB/config table va xu ly validation.
            </div>
        </article>
    </section>
@endsection
