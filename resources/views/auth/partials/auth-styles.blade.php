<style>
    .auth-screen {
        background:
            linear-gradient(120deg, rgba(249, 252, 241, 0.96), rgba(239, 247, 232, 0.9)),
            url('//theme.hstatic.net/200000157781/1001036201/14/banner1.jpg?v=1061') center/cover no-repeat;
        min-height: 620px;
        padding: 58px 0 78px;
    }

    .auth-grid {
        align-items: stretch;
        display: grid;
        gap: 26px;
        grid-template-columns: minmax(0, 0.95fr) minmax(360px, 440px);
    }

    .auth-intro {
        align-content: center;
        display: grid;
        min-height: 520px;
        padding: 20px 0;
    }

    .auth-kicker {
        align-items: center;
        color: #6d8d24;
        display: inline-flex;
        font-size: 13px;
        font-weight: 800;
        gap: 8px;
        letter-spacing: .04em;
        margin-bottom: 14px;
        text-transform: uppercase;
    }

    .auth-title {
        color: #1f3217;
        font-family: Manrope, Arial, sans-serif;
        font-size: 44px;
        font-weight: 800;
        line-height: 1.08;
        margin: 0 0 14px;
        max-width: 620px;
    }

    .auth-copy {
        color: #52634d;
        font-size: 16px;
        line-height: 1.75;
        margin: 0;
        max-width: 560px;
    }

    .auth-points {
        display: grid;
        gap: 10px;
        margin-top: 24px;
        max-width: 520px;
    }

    .auth-point {
        align-items: center;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(125, 158, 83, 0.22);
        border-radius: 8px;
        color: #2f4428;
        display: flex;
        gap: 10px;
        padding: 11px 13px;
    }

    .auth-point i {
        color: #74a721;
        font-size: 17px;
    }

    .auth-card {
        background: #fff;
        border: 1px solid #dde8d7;
        border-radius: 8px;
        box-shadow: 0 24px 70px rgba(35, 59, 23, 0.14);
        padding: 30px;
    }

    .auth-card-head {
        margin-bottom: 22px;
    }

    .auth-card-head h1 {
        color: #1f3217;
        font-family: Manrope, Arial, sans-serif;
        font-size: 28px;
        font-weight: 800;
        line-height: 1.2;
        margin: 0 0 7px;
    }

    .auth-card-head p {
        color: #66745f;
        line-height: 1.55;
        margin: 0;
    }

    .auth-alert {
        background: #fff3f0;
        border: 1px solid #ffd3ca;
        border-radius: 8px;
        color: #9c341f;
        font-weight: 700;
        margin-bottom: 16px;
        padding: 11px 13px;
    }

    .auth-field {
        display: grid;
        gap: 7px;
        margin-bottom: 15px;
    }

    .auth-field label {
        color: #32462d;
        font-size: 13px;
        font-weight: 800;
        margin: 0;
    }

    .auth-control {
        align-items: center;
        border: 1px solid #d8e3d2;
        border-radius: 8px;
        display: flex;
        gap: 10px;
        min-height: 46px;
        padding: 0 13px;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .auth-control:focus-within {
        border-color: #8fbd3e;
        box-shadow: 0 0 0 4px rgba(143, 189, 62, 0.12);
    }

    .auth-control i {
        color: #7d936f;
        font-size: 16px;
    }

    .auth-control input {
        background: transparent;
        border: 0;
        color: #1f3217;
        flex: 1;
        font-size: 14px;
        min-width: 0;
        outline: 0;
    }

    .auth-row {
        align-items: center;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin: 3px 0 20px;
    }

    .auth-check {
        align-items: center;
        color: #485a42;
        display: inline-flex;
        font-size: 13px;
        font-weight: 700;
        gap: 8px;
    }

    .auth-link {
        color: #6f9e1f;
        font-size: 13px;
        font-weight: 800;
    }

    .auth-submit {
        align-items: center;
        background: linear-gradient(135deg, #83b735, #5c931b);
        border: 0;
        border-radius: 8px;
        color: #fff;
        display: inline-flex;
        font-weight: 800;
        gap: 8px;
        justify-content: center;
        min-height: 48px;
        padding: 0 18px;
        width: 100%;
    }

    .auth-submit:hover,
    .auth-submit:focus {
        color: #fff;
        filter: brightness(.97);
    }

    .auth-switch {
        border-top: 1px solid #edf2e9;
        color: #617059;
        font-size: 14px;
        margin-top: 22px;
        padding-top: 18px;
        text-align: center;
    }

    .auth-small {
        color: #74806d;
        font-size: 12px;
        line-height: 1.55;
        margin-top: 13px;
        text-align: center;
    }

    @media (max-width: 991px) {
        .auth-grid {
            grid-template-columns: 1fr;
        }

        .auth-intro {
            min-height: auto;
        }

        .auth-title {
            font-size: 34px;
        }
    }

    @media (max-width: 575px) {
        .auth-screen {
            padding: 34px 0 52px;
        }

        .auth-card {
            padding: 22px;
        }

        .auth-title {
            font-size: 29px;
        }

        .auth-row {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
