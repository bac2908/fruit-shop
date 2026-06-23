<style>
    .auth-screen {
        background:
            radial-gradient(circle at 18% 16%, rgba(255, 245, 188, 0.58) 0, rgba(255, 245, 188, 0) 32%),
            radial-gradient(circle at 84% 18%, rgba(184, 224, 128, 0.45) 0, rgba(184, 224, 128, 0) 34%),
            linear-gradient(135deg, #f8fbef 0%, #eef8e8 46%, #f9fbf1 100%);
        min-height: 620px;
        padding: 84px 0 86px;
    }

    .auth-grid {
        align-items: center;
        display: grid;
        gap: 34px;
        grid-template-columns: minmax(0, 0.95fr) minmax(340px, 420px);
    }

    .auth-intro {
        align-content: center;
        display: grid;
        min-height: 480px;
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
        padding: 28px;
    }

    .auth-card-head {
        margin-bottom: 18px;
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

    .auth-alert-success {
        background: #f0fae8;
        border-color: #cce9b6;
        color: #3f7218;
    }

    .auth-error-list {
        display: grid;
        gap: 4px;
        line-height: 1.45;
        margin: 0;
        padding-left: 18px;
    }

    .auth-social-block {
        display: grid;
        gap: 0;
        margin: 0 0 2px;
    }

    .auth-social-block-bottom {
        margin: 18px 0 0;
    }

    .auth-social-block-bottom .auth-separator {
        margin: 0 0 14px;
    }

    .auth-google-btn {
        align-items: center;
        background: #fff;
        border: 1px solid #ccd9c4;
        border-radius: 8px;
        color: #23341e;
        display: flex;
        font-size: 14px;
        font-weight: 800;
        gap: 10px;
        justify-content: center;
        min-height: 46px;
        padding: 0 16px;
        text-decoration: none;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        width: 100%;
    }

    .auth-google-btn:hover,
    .auth-google-btn:focus {
        border-color: #83b735;
        box-shadow: 0 8px 22px rgba(35, 59, 23, 0.1);
        color: #1f3217;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .auth-google-mark {
        align-items: center;
        background: conic-gradient(from -45deg, #4285f4 0 25%, #34a853 0 50%, #fbbc05 0 75%, #ea4335 0 100%);
        border-radius: 50%;
        color: #fff;
        display: inline-flex;
        flex: 0 0 24px;
        font-family: Arial, sans-serif;
        font-size: 14px;
        font-weight: 800;
        height: 24px;
        justify-content: center;
        line-height: 1;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.22);
        width: 24px;
    }

    .auth-separator {
        align-items: center;
        color: #8a9782;
        display: flex;
        font-size: 12px;
        font-weight: 700;
        gap: 11px;
        margin: 14px 0 15px;
        text-align: center;
    }

    .auth-separator::before,
    .auth-separator::after {
        background: #edf2e9;
        content: "";
        flex: 1;
        height: 1px;
    }

    .auth-field {
        display: grid;
        gap: 6px;
        margin-bottom: 13px;
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
        gap: 9px;
        height: 44px;
        padding: 0 11px;
        transition: border-color .2s ease, box-shadow .2s ease;
        width: 100%;
    }

    .auth-control:focus-within {
        border-color: #83b735;
        box-shadow: 0 0 0 3px rgba(131, 183, 53, 0.11);
    }

    .auth-control i {
        color: #7d936f;
        font-size: 16px;
    }

    .auth-control > input {
        appearance: none;
        -webkit-appearance: none;
        align-self: center;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        box-sizing: border-box;
        color: #1f3217;
        flex: 1;
        font-size: 14px;
        height: 24px;
        line-height: 24px;
        margin: 0;
        min-width: 0;
        outline: 0 !important;
        padding: 0;
        width: 100%;
    }

    .auth-control > input:focus {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        outline: 0 !important;
    }

    .auth-control > input:-webkit-autofill,
    .auth-control > input:-webkit-autofill:hover,
    .auth-control > input:-webkit-autofill:focus {
        -webkit-text-fill-color: #1f3217;
        box-shadow: 0 0 0 1000px #fff inset;
        transition: background-color 9999s ease-out;
    }

    .auth-password-toggle {
        align-items: center;
        background: transparent;
        border: 0;
        color: #78906b;
        display: inline-flex;
        flex: 0 0 28px;
        height: 28px;
        justify-content: center;
        margin: 0;
        padding: 0;
    }

    .auth-password-toggle:hover,
    .auth-password-toggle:focus {
        color: #5c931b;
        outline: 0;
    }

    .auth-password-toggle i {
        font-size: 15px;
    }

    .auth-strength {
        display: grid;
        gap: 6px;
        margin-top: 1px;
    }

    .auth-strength-track {
        display: grid;
        gap: 5px;
        grid-template-columns: repeat(3, 1fr);
    }

    .auth-strength-bar {
        background: #e4eadf;
        border-radius: 99px;
        display: block;
        height: 5px;
        transition: background-color .2s ease;
    }

    .auth-strength[data-score="1"] .auth-strength-bar:nth-child(1) {
        background: #d94c3a;
    }

    .auth-strength[data-score="2"] .auth-strength-bar:nth-child(-n+2) {
        background: #d9a12d;
    }

    .auth-strength[data-score="3"] .auth-strength-bar {
        background: #6fae25;
    }

    .auth-strength-text {
        color: #65745e;
        font-size: 12px;
        line-height: 1.4;
    }

    .auth-strength-label {
        color: #344a2d;
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

    .auth-link-disabled {
        color: #9aa691;
        cursor: default;
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
        min-height: 44px;
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
