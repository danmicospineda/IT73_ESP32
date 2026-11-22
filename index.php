<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b111e">
    <title>ESP32 | Sensor Dashboard</title>
    <style>
        :root {
            color-scheme: dark;
            --background: #0b111e;
            --surface: #141d2d;
            --surface-alt: #182335;
            --border: #29364a;
            --text: #edf4fc;
            --muted: #8e9bb0;
            --muted-bright: #aebbd0;
            --accent: #63d8c5;
            --accent-blue: #79a9ff;
            --danger: #ff7785;
            --warning: #ffbd75;
        }

        * { box-sizing: border-box; }
        html { min-width: 320px; }
        body {
            margin: 0;
            background:
                radial-gradient(ellipse 65% 50% at 100% -15%, rgba(56, 111, 166, 0.14), transparent 80%),
                radial-gradient(ellipse 50% 35% at 0% 15%, rgba(43, 148, 147, 0.07), transparent 80%),
                var(--background);
            color: var(--text);
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }
        svg { display: block; }
        .app-shell { width: min(100% - 88px, 1376px); margin: 0 auto; }

        .topbar {
            border-bottom: 1px solid rgba(135, 161, 190, 0.14);
            background: rgba(12, 18, 31, 0.45);
        }
        .topbar-inner {
            min-height: 90px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand-mark {
            height: 43px;
            width: 43px;
            border: 1px solid rgba(99, 216, 197, .3);
            border-radius: 13px;
            display: grid;
            place-items: center;
            color: var(--accent);
            background: linear-gradient(135deg, rgba(99, 216, 197, .14), rgba(99, 216, 197, .035));
        }
        .brand-name { font-weight: 760; letter-spacing: .015em; font-size: 17px; }
        .brand-sub { font-size: 11px; color: var(--muted); letter-spacing: .14em; margin-top: 4px; }
        .header-right { display: flex; gap: 12px; align-items: center; }
        .header-tag {
            font-size: 12px; letter-spacing: .08em; color: var(--muted);
            border-right: 1px solid var(--border); padding-right: 20px; margin-right: 8px;
        }
        .status-bar {
            min-width: 185px;
            display: inline-flex; align-items: center; justify-content: center;
            gap: 10px; padding: 12px 16px;
            background: rgba(24, 35, 53, .8);
            border: 1px solid var(--border); border-radius: 99px;
            color: var(--muted-bright); font-size: 12px; font-weight: 650;
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; flex: 0 0 auto; }
        .dot.online { background: #63d8a8; box-shadow: 0 0 0 4px rgba(99, 216, 168, .12); }
        .dot.offline { background: var(--danger); box-shadow: 0 0 0 4px rgba(255, 119, 133, .12); }
        .dot.stale { background: var(--warning); box-shadow: 0 0 0 4px rgba(255, 189, 117, .12); }

        main { padding: 50px 0 44px; }
        .eyebrow { color: var(--accent); text-transform: uppercase; font-size: 11px; letter-spacing: .19em; font-weight: 760; }
        .overview-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 44px; }
        h1 { font-size: clamp(32px, 3.5vw, 46px); font-weight: 750; letter-spacing: -.046em; margin: 12px 0 11px; line-height: 1.14; }
        .intro { margin: 0; font-size: 14px; color: var(--muted); line-height: 1.6; }
        .overview-aside { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
        .metric-count { font-size: 41px; letter-spacing: -.05em; line-height: 1; font-weight: 750; }
        .metric-count-label { font-size: 12px; color: var(--muted); line-height: 1.5; }

        .section-heading { display: flex; gap: 16px; align-items: baseline; justify-content: space-between; margin-bottom: 19px; }
        .section-heading h2 { margin: 0; font-size: 18px; letter-spacing: -.025em; font-weight: 690; }
        .section-note { font-size: 12px; color: var(--muted); }

        #cards-wrap { display: none; }
        .sensor-grid { grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 18px; }
        .card {
            min-width: 0;
            position: relative;
            overflow: hidden;
            background: linear-gradient(155deg, rgba(26, 38, 57, .96), rgba(17, 26, 41, .99));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px 27px 27px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, .1);
        }
        .card::after {
            content: ""; position: absolute; width: 160px; height: 160px;
            border-radius: 50%; top: -100px; right: -80px;
            background: rgba(125, 163, 220, .045);
            pointer-events: none;
        }
        .card-climate { grid-column: span 6; min-height: 316px; }
        .card-rain, .card-motion { grid-column: span 3; min-height: 316px; }
        .card-distance, .card-light { grid-column: span 6; min-height: 250px; }
        .card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; position: relative; z-index: 1; }
        .sensor-identity { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .sensor-icon { width: 41px; height: 41px; border-radius: 12px; display: grid; place-items: center; flex: 0 0 auto; }
        .sensor-icon.mint { background: rgba(99, 216, 197, .1); color: var(--accent); }
        .sensor-icon.blue { background: rgba(121, 169, 255, .11); color: var(--accent-blue); }
        .sensor-icon.amber { background: rgba(255, 189, 117, .1); color: var(--warning); }
        .sensor-name { font-size: 14px; font-weight: 690; letter-spacing: -.01em; line-height: 1.3; }
        .sensor-subtitle { font-size: 11px; color: var(--muted); margin-top: 4px; }
        .sensor-code {
            border: 1px solid rgba(132, 155, 185, .2);
            border-radius: 7px; padding: 6px 9px; color: var(--muted-bright);
            font-size: 10px; font-weight: 700; letter-spacing: .05em; white-space: nowrap;
        }
        .card-body { position: relative; z-index: 1; }
        .kicker { display: block; color: var(--muted); font-size: 10px; font-weight: 720; letter-spacing: .13em; text-transform: uppercase; }
        .large-reading { font-size: clamp(24px, 2.6vw, 34px); line-height: 1.18; font-weight: 730; letter-spacing: -.04em; overflow-wrap: anywhere; }
        .reading-unit { font-size: 17px; font-weight: 510; letter-spacing: -.02em; color: var(--muted-bright); margin-left: 4px; }

        .climate-layout { display: grid; grid-template-columns: minmax(155px, 1fr) minmax(190px, 1.1fr); align-items: center; gap: 18px; padding-top: 26px; }
        .dial-wrap { display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .temp-dial {
            --progress: 0%;
            width: 158px; height: 158px; padding: 10px; border-radius: 50%;
            background: conic-gradient(from 215deg, var(--accent) 0% var(--progress), #2b3b4d var(--progress) 100%);
            box-shadow: 0 0 0 1px rgba(99, 216, 197, .05), 0 0 28px rgba(99, 216, 197, .05);
        }
        .temp-inner {
            width: 100%; height: 100%; border-radius: 50%;
            background: #141e2d; border: 7px solid #182436;
            display: flex; align-items: center; justify-content: center; flex-direction: column;
        }
        .temp-inner .kicker { margin: 0 0 7px; font-size: 9px; }
        .temp-inner .large-reading { font-size: 25px; letter-spacing: -.05em; white-space: nowrap; }
        .temp-inner .reading-unit { font-size: 13px; margin-left: 2px; }
        .dial-scale { display: flex; justify-content: space-between; width: 153px; color: var(--muted); font-size: 10px; }
        .humidity-area { padding: 8px 0; }
        .humidity-area .large-reading { margin: 12px 0 21px; font-size: 32px; }
        .progress-track { height: 9px; border-radius: 99px; background: #2b3a4c; overflow: hidden; }
        .progress-fill { display: block; height: 100%; width: 0%; border-radius: 99px; background: linear-gradient(90deg, #4faaa8, #73e1ca); transition: width .55s ease; }
        .progress-fill.distance-fill { background: linear-gradient(90deg, #537bdb, #8eb8ff); }
        .progress-axis { display: flex; justify-content: space-between; font-size: 11px; color: var(--muted); margin-top: 10px; }
        .detail-note { font-size: 11px; color: var(--muted); line-height: 1.5; margin: 20px 0 0; }

        .state-body { margin-top: 48px; }
        .state-body .large-reading { margin-top: 14px; min-height: 48px; }
        .state-footer { display: flex; align-items: center; gap: 10px; border-top: 1px solid rgba(145, 164, 191, .14); padding-top: 20px; margin-top: 24px; color: var(--muted); font-size: 11px; }
        .mini-signal { width: 7px; height: 7px; border-radius: 50%; background: var(--accent-blue); box-shadow: 0 0 0 4px rgba(121, 169, 255, .1); flex: 0 0 auto; }
        .state-footer.mint-signal .mini-signal { background: var(--accent); box-shadow: 0 0 0 4px rgba(99, 216, 197, .1); }
        .distance-layout { display: grid; grid-template-columns: minmax(120px, .65fr) minmax(160px, 1fr); gap: 30px; align-items: end; margin-top: 34px; }
        .distance-layout .large-reading { margin-top: 12px; white-space: nowrap; }
        .distance-track-wrap { padding-bottom: 4px; }
        .distance-track-wrap .kicker { margin-bottom: 14px; }
        .light-layout { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 36px; }
        .light-layout .large-reading { margin-top: 12px; }
        .light-emblem {
            width: 103px; height: 103px; flex: 0 0 auto; border-radius: 21px;
            display: grid; place-items: center; color: var(--warning);
            background: radial-gradient(circle, rgba(255, 188, 117, .13), rgba(255, 188, 117, .04) 65%, transparent 68%);
            border: 1px solid rgba(255, 189, 117, .12);
        }

        .health-panel { margin-top: 28px; background: rgba(20, 29, 45, .78); border: 1px solid var(--border); border-radius: 18px; padding: 25px 28px 27px; }
        .health-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 21px; }
        .health-head h2 { margin: 0; font-size: 15px; letter-spacing: -.01em; }
        .health-head p { margin: 7px 0 0; color: var(--muted); font-size: 12px; }
        .health-tag { color: var(--muted); font-size: 10px; letter-spacing: .11em; text-transform: uppercase; font-weight: 700; white-space: nowrap; }
        .sensor-status { display: none; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; }
        .sensor-status span {
            background: #192537; border: 1px solid rgba(139, 167, 194, .15);
            border-radius: 11px; padding: 15px 12px; display: flex; align-items: center; gap: 9px;
            font-size: 12px; color: var(--text); font-weight: 650; min-width: 0;
        }
        .sensor-status span::before { content: ""; width: 8px; height: 8px; flex: 0 0 auto; border-radius: 50%; background: currentColor; }
        .sensor-status span.ok::before { color: var(--accent); }
        .sensor-status span.fail::before { color: var(--danger); }
        .sensor-status span.unknown::before { color: var(--warning); }
        .sensor-status span.stale::before { color: var(--muted); }
        .sensor-status span::after { content: attr(data-state); color: var(--muted); font-size: 10px; font-weight: 500; margin-left: auto; }
        .sensor-status span.fail::after { color: var(--danger); }
        .sensor-status span.unknown::after { color: var(--warning); }

        .warning-banner {
            background: rgba(104, 36, 51, .22); border: 1px solid rgba(255, 119, 133, .36);
            border-left: 4px solid var(--danger); border-radius: 15px;
            padding: 22px 24px; margin-bottom: 26px; color: #ffe4e7;
        }
        .warning-inner { display: flex; align-items: flex-start; gap: 13px; }
        .warning-icon { color: var(--danger); margin-top: 2px; flex: 0 0 auto; }
        .warning-banner h2 { margin: 0; font-size: 17px; font-weight: 730; }
        .warning-banner .missing { margin-top: 9px; color: #e0adb7; font-size: 13px; line-height: 1.7; }
        .warning-banner .missing strong { color: #ffe4e7; font-weight: 700; }

        .waiting-msg {
            background: rgba(20, 29, 45, .84); border: 1px dashed #3a4a60;
            border-radius: 18px; min-height: 234px; display: grid; place-content: center;
            justify-items: center; text-align: center; padding: 26px; color: var(--muted);
        }
        .waiting-icon { color: var(--accent-blue); margin-bottom: 17px; }
        .waiting-msg strong { display: block; font-size: 16px; font-weight: 680; color: var(--text); margin-bottom: 8px; }
        .waiting-msg p { font-size: 13px; margin: 0; }

        .footer { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-top: 30px; padding-top: 24px; border-top: 1px solid rgba(135, 161, 190, .14); font-size: 11px; color: var(--muted); }
        .footer strong { color: var(--muted-bright); font-weight: 620; }
        .footer-right { display: flex; gap: 20px; }

        @media (max-width: 1120px) {
            .app-shell { width: min(100% - 48px, 1376px); }
            .card-climate { grid-column: span 12; }
            .card-rain, .card-motion { grid-column: span 6; min-height: 265px; }
            .card-distance, .card-light { grid-column: span 6; }
            .state-body { margin-top: 35px; }
            .sensor-status { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 680px) {
            .app-shell { width: min(100% - 32px, 1376px); }
            .topbar-inner { min-height: 76px; }
            .header-tag, .overview-aside { display: none; }
            .status-bar { min-width: 0; max-width: 175px; padding: 10px 12px; text-align: left; line-height: 1.4; }
            .brand-mark { height: 38px; width: 38px; }
            .brand-name { font-size: 15px; }
            .brand-sub { font-size: 9px; }
            main { padding-top: 32px; }
            .overview-heading { margin-bottom: 34px; }
            .section-note { display: none; }
            .sensor-grid { gap: 13px; }
            .card { padding: 22px 20px; border-radius: 16px; }
            .card-climate, .card-rain, .card-motion, .card-distance, .card-light { grid-column: span 12; min-height: 0; }
            .climate-layout { grid-template-columns: minmax(140px, 1fr) minmax(0, 1fr); gap: 12px; }
            .temp-dial { height: 138px; width: 138px; }
            .dial-scale { width: 135px; }
            .humidity-area .large-reading { font-size: 27px; }
            .state-body { margin-top: 27px; }
            .state-body .large-reading { min-height: 0; }
            .state-footer { margin-top: 25px; }
            .distance-layout { gap: 18px; margin-top: 30px; }
            .light-layout { margin-top: 27px; }
            .light-emblem { height: 80px; width: 80px; }
            .health-panel { padding: 22px 18px; }
            .sensor-status { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .sensor-status span { font-size: 11px; }
            .footer-right { gap: 12px; }
        }
        @media (max-width: 385px) {
            .status-bar { font-size: 11px; max-width: 140px; }
            .climate-layout { grid-template-columns: 1fr; }
            .humidity-area { padding-top: 10px; }
            .distance-layout { grid-template-columns: 1fr; gap: 24px; }
            .sensor-status { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="app-shell topbar-inner">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true">
                    <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/><path d="m9 12 2 2 4-4"/>
                    </svg>
                </div>
                <div><div class="brand-name">IT73</div><div class="brand-sub">SENSOR DASHBOARD</div></div>
            </div>
            <div class="header-right">
                <span class="header-tag">LIVE MONITORING</span>
                <div class="status-bar" role="status" aria-live="polite">
                    <div class="dot offline" id="status-dot" aria-hidden="true"></div>
                    <span id="status-text">Loading...</span>
                </div>
            </div>
        </div>
    </header>

    <main class="app-shell">
        <section class="overview-heading" aria-label="Dashboard overview">
            <div>
                <div class="eyebrow">Device telemetry</div>
                <h1>Sensor overview</h1>
                <p class="intro">Real-time readings and connection health from the ESP32.</p>
            </div>
        </section>

        <div id="warning-banner" class="warning-banner" role="alert" style="display:none;">
            <div class="warning-inner">
                <svg class="warning-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.3 3.6 2.2 17.5A2 2 0 0 0 4 20.5h16a2 2 0 0 0 1.8-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>
                </svg>
                <div>
                    <h2>System halted — sensor check failed</h2>
                    <div class="missing">
                        Missing: <strong id="missing-list"></strong><br>
                        A failed check may mean a missing sensor, a wiring issue, or an invalid reading.
                    </div>
                </div>
            </div>
        </div>

        <section aria-label="Current sensor readings">
            <div class="section-heading">
                <h2>Live readings</h2>
                <span class="section-note">Automatically updated every 3 seconds</span>
            </div>

            <div id="cards-wrap" class="sensor-grid">
                <article class="card card-climate" aria-label="DHT11 temperature and humidity sensor">
                    <div class="card-top">
                        <div class="sensor-identity">
                            <span class="sensor-icon mint" aria-hidden="true">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.8V5a3 3 0 0 0-6 0v9.8a5 5 0 1 0 6 0Z"/><path d="M11 11v7"/></svg>
                            </span>
                            <span><span class="sensor-name">Climate</span><span class="sensor-subtitle" style="display:block">Temperature &amp; humidity</span></span>
                        </div>
                        <span class="sensor-code">DHT11</span>
                    </div>
                    <div class="card-body climate-layout">
                        <div class="dial-wrap">
                            <div class="temp-dial" id="temp-gauge" role="img" aria-label="Temperature gauge, scale zero to fifty degrees Celsius">
                                <div class="temp-inner">
                                    <span class="kicker">Temperature</span>
                                    <div class="large-reading" id="v-temp">--</div>
                                </div>
                            </div>
                            <div class="dial-scale"><span>0°C</span><span>50°C</span></div>
                        </div>
                        <div class="humidity-area">
                            <span class="kicker">Relative humidity</span>
                            <div class="large-reading" id="v-hum">--</div>
                            <div class="progress-track" id="humidity-track" role="progressbar" aria-label="Humidity" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <span class="progress-fill" id="humidity-bar"></span>
                            </div>
                            <div class="progress-axis"><span>0%</span><span>100%</span></div>
                            <p class="detail-note">Two readings from one sensor module.</p>
                        </div>
                    </div>
                </article>

                <article class="card card-rain" aria-label="Raindrop sensor">
                    <div class="card-top">
                        <div class="sensor-identity">
                            <span class="sensor-icon blue" aria-hidden="true">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 15a4 4 0 0 0-2.8-6.8A6 6 0 0 0 5.5 10 4 4 0 0 0 6 18h2"/><path d="m11 17-1 3M16 17l-1 3"/></svg>
                            </span>
                            <span><span class="sensor-name">Rain detection</span><span class="sensor-subtitle" style="display:block">Raindrop module</span></span>
                        </div>
                    </div>
                    <div class="card-body state-body">
                        <span class="kicker">Current state</span>
                        <div class="large-reading" id="v-rain">--</div>
                        <div class="state-footer"><span class="mini-signal"></span>Digital sensor output</div>
                    </div>
                </article>

                <article class="card card-motion" aria-label="PIR motion sensor">
                    <div class="card-top">
                        <div class="sensor-identity">
                            <span class="sensor-icon mint" aria-hidden="true">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M7.7 7.7a6 6 0 0 0 0 8.6M16.3 7.7a6 6 0 0 1 0 8.6M4.9 4.9a10 10 0 0 0 0 14.2M19.1 4.9a10 10 0 0 1 0 14.2"/></svg>
                            </span>
                            <span><span class="sensor-name">Motion</span><span class="sensor-subtitle" style="display:block">PIR sensor</span></span>
                        </div>
                    </div>
                    <div class="card-body state-body">
                        <span class="kicker">Current state</span>
                        <div class="large-reading" id="v-motion">--</div>
                        <div class="state-footer mint-signal"><span class="mini-signal"></span>Digital sensor output</div>
                    </div>
                </article>

                <article class="card card-distance" aria-label="HC-SR04 distance sensor">
                    <div class="card-top">
                        <div class="sensor-identity">
                            <span class="sensor-icon blue" aria-hidden="true">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5v14M20 5v14M7 12h10M10 9l-3 3 3 3M14 9l3 3-3 3"/></svg>
                            </span>
                            <span><span class="sensor-name">Distance</span><span class="sensor-subtitle" style="display:block">Ultrasonic measurement</span></span>
                        </div>
                        <span class="sensor-code">HC-SR04</span>
                    </div>
                    <div class="card-body distance-layout">
                        <div>
                            <span class="kicker">Measured distance</span>
                            <div class="large-reading" id="v-dist">--</div>
                        </div>
                        <div class="distance-track-wrap">
                            <span class="kicker">Visual range</span>
                            <div class="progress-track" id="distance-track" role="progressbar" aria-label="Distance" aria-valuemin="0" aria-valuemax="400" aria-valuenow="0">
                                <span class="progress-fill distance-fill" id="distance-bar"></span>
                            </div>
                            <div class="progress-axis"><span>0 cm</span><span>400 cm</span></div>
                        </div>
                    </div>
                </article>

                <article class="card card-light" aria-label="LM393 light sensor">
                    <div class="card-top">
                        <div class="sensor-identity">
                            <span class="sensor-icon amber" aria-hidden="true">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"/></svg>
                            </span>
                            <span><span class="sensor-name">Light detection</span><span class="sensor-subtitle" style="display:block">Ambient light state</span></span>
                        </div>
                        <span class="sensor-code">LM393</span>
                    </div>
                    <div class="card-body light-layout">
                        <div>
                            <span class="kicker">Current state</span>
                            <div class="large-reading" id="v-light">--</div>
                            <p class="detail-note">Digital sensor output</p>
                        </div>
                        <div class="light-emblem" aria-hidden="true">
                            <svg width="47" height="47" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"/></svg>
                        </div>
                    </div>
                </article>
            </div>

            <div id="waiting-msg" class="waiting-msg" role="status">
                <svg class="waiting-icon" width="37" height="37" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9a14 14 0 0 1 20 0M5 12a10 10 0 0 1 14 0M8.5 15.5a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r="1" fill="currentColor" stroke="none"/></svg>
                <strong>Waiting for ESP32 data</strong>
                <p>Sensor readings will appear here once your device sends data.</p>
            </div>
        </section>

        <section class="health-panel" aria-label="Sensor connection health">
            <div class="health-head">
                <div><h2>Module health</h2><p>Reported sensor health. Digital outputs alone cannot verify a physical connection.</p></div>
                <span class="health-tag">Diagnostics</span>
            </div>
            <div class="sensor-status" id="sensor-status-row" style="display:none;">
                <span id="s-dht"></span>
                <span id="s-rain"></span>
                <span id="s-pir"></span>
                <span id="s-sonar"></span>
                <span id="s-light"></span>
            </div>
            <p id="health-waiting" class="section-note" style="margin:0;">Awaiting sensor diagnostics...</p>
        </section>

        <footer class="footer">
            <div>ESP32 <strong>Sensor Monitor</strong></div>
            <div class="footer-right">
                <span>Polling interval: <strong>3 seconds</strong></span>
                <span>Last refreshed: <strong id="refresh-time">Not yet</strong></span>
            </div>
        </footer>
    </main>

    <script>
        const POLL_INTERVAL = 3000;
        // Allow ordinary Wi-Fi / HTTP delays before calling the ESP32 inactive.
        const ACTIVE_WINDOW_SECONDS = 30;
        const OFFLINE_WINDOW_SECONDS = 90;
        const READING_STORAGE_KEY = 'it73-esp32-last-readings-v1';
        const READING_FIELDS = {
            temperature: ['v-temp', ' °C'],
            humidity: ['v-hum', ' %'],
            rain_status: ['v-rain', ''],
            motion: ['v-motion', ''],
            distance: ['v-dist', ' cm'],
            light: ['v-light', '']
        };

        let lastReadings = {};
        let lastReadingModified = 0;
        let polling = false;

        // Keep the previous readings through a refresh, without changing ESP32_data.php.
        try {
            const saved = JSON.parse(localStorage.getItem(READING_STORAGE_KEY) || 'null');
            if (saved && saved.readings && typeof saved.readings === 'object') {
                for (const key of Object.keys(READING_FIELDS)) {
                    if (saved.readings[key] !== null && saved.readings[key] !== undefined) {
                        lastReadings[key] = saved.readings[key];
                    }
                }
                lastReadingModified = Number(saved.lastModified) || 0;
            }
        } catch (err) {
            // Private browsing/storage restrictions should not stop live readings.
        }

        function hasReadings() {
            return Object.keys(lastReadings).length > 0;
        }

        function setStatus(dotClass, text) {
            document.getElementById('status-dot').className = 'dot ' + dotClass;
            document.getElementById('status-text').textContent = text;
        }

        function setSensorBadge(id, label, state) {
            const el = document.getElementById(id);
            const classes = { Ready: 'ok', Offline: 'fail', Unverified: 'unknown', Stale: 'stale' };
            el.textContent = label;
            el.className = classes[state] || 'unknown';
            el.setAttribute('data-state', state);
            el.setAttribute('aria-label', label + ': ' + state);
        }

        // A 0/1 digital output does not prove a module is connected.
        function sensorState(ok, deviceFresh, digitalOnly) {
            if (!deviceFresh) return 'Stale';
            if (ok !== true && ok !== false) return 'Unverified';
            if (ok === false) return 'Offline';
            return digitalOnly ? 'Unverified' : 'Ready';
        }

        function showSensorStates(data, fresh) {
            const sensors = [
                ['s-dht', 'DHT11', data.dht_ok, false],
                ['s-rain', 'Raindrop', data.rain_ok, true],
                ['s-pir', 'PIR', data.pir_ok, true],
                ['s-sonar', 'HC-SR04', data.sonar_ok, false],
                ['s-light', 'LM393', data.light_ok, true]
            ];
            for (const [id, label, ok, digitalOnly] of sensors) {
                setSensorBadge(id, label, sensorState(ok, fresh, digitalOnly));
            }
            document.getElementById('sensor-status-row').style.display = 'grid';
            document.getElementById('health-waiting').style.display = 'none';
        }

        function updateVisualization(data) {
            const temperature = Number(data.temperature);
            if (data.temperature !== null && data.temperature !== undefined && Number.isFinite(temperature)) {
                const percent = Math.max(0, Math.min(100, temperature / 50 * 100));
                document.getElementById('temp-gauge').style.setProperty('--progress', percent + '%');
                document.getElementById('temp-gauge').setAttribute('aria-label', 'Temperature: ' + data.temperature + ' degrees Celsius, visual scale zero to fifty degrees');
            } else {
                document.getElementById('temp-gauge').style.setProperty('--progress', '0%');
                document.getElementById('temp-gauge').setAttribute('aria-label', 'Temperature unavailable');
            }

            const humidity = Number(data.humidity);
            const humidityValid = data.humidity !== null && data.humidity !== undefined && Number.isFinite(humidity);
            const humidityValue = humidityValid ? Math.max(0, Math.min(100, humidity)) : 0;
            document.getElementById('humidity-bar').style.width = humidityValue + '%';
            document.getElementById('humidity-track').setAttribute('aria-valuenow', String(humidityValue));
            document.getElementById('humidity-track').setAttribute('aria-valuetext', humidityValid ? String(data.humidity) + ' percent' : 'Unavailable');

            const distance = Number(data.distance);
            const distanceValid = data.distance !== null && data.distance !== undefined && Number.isFinite(distance);
            const distanceValue = distanceValid ? Math.max(0, Math.min(400, distance)) : 0;
            document.getElementById('distance-bar').style.width = (distanceValue / 400 * 100) + '%';
            document.getElementById('distance-track').setAttribute('aria-valuenow', String(distanceValue));
            document.getElementById('distance-track').setAttribute('aria-valuetext', distanceValid ? String(data.distance) + ' centimeters' : 'Unavailable');
        }

        function renderReadings() {
            if (!hasReadings()) return;
            for (const [key, [id, unit]] of Object.entries(READING_FIELDS)) {
                const value = lastReadings[key];
                document.getElementById(id).textContent = value === undefined ? 'N/A' : String(value) + unit;
            }
            updateVisualization(lastReadings);
        }

        // This is the key distinction: an inactive ESP32 does not erase old readings.
        function showReadingArea() {
            const available = hasReadings();
            document.getElementById('cards-wrap').style.display = available ? 'grid' : 'none';
            document.getElementById('waiting-msg').style.display = available ? 'none' : 'grid';
        }

        function storeNewReadings(data) {
            const modified = Number(data._last_modified) || Math.floor(Date.now() / 1000);
            // A late/out-of-order server response must not replace newer cached data.
            if (modified < lastReadingModified) return;
            let changed = false;
            for (const key of Object.keys(READING_FIELDS)) {
                if (data[key] !== null && data[key] !== undefined) {
                    lastReadings[key] = data[key];
                    changed = true;
                }
            }
            if (!changed) return; // Warning-only POSTs have no reading fields.
            lastReadingModified = modified;
            try {
                localStorage.setItem(READING_STORAGE_KEY, JSON.stringify({
                    readings: lastReadings, lastModified: lastReadingModified
                }));
            } catch (err) {
                // The live page still retains values if browser storage is unavailable.
            }
            renderReadings();
        }

        function showNoReadingMessage(title, detail) {
            document.querySelector('#waiting-msg strong').textContent = title;
            document.querySelector('#waiting-msg p').textContent = detail;
            showReadingArea();
        }

        async function poll() {
            if (polling) return;
            polling = true;
            try {
                const res = await fetch('ESP32_data.php', { cache: 'no-store' });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                document.getElementById('refresh-time').textContent = new Date().toLocaleString();

                if (data.error) {
                    setStatus('offline', data.error === 'No data yet' ? 'No Data Received' : data.error);
                    document.getElementById('warning-banner').style.display = 'none';
                    if (hasReadings()) showSensorStates({}, false);
                    else {
                        document.getElementById('sensor-status-row').style.display = 'none';
                        document.getElementById('health-waiting').style.display = 'block';
                    }
                    showNoReadingMessage('Waiting for ESP32 data', 'Sensor readings will appear here once your device sends data.');
                    return;
                }

                const modified = Number(data._last_modified) || 0;
                const secondsAgo = modified > 0 ? Math.max(0, Math.floor(Date.now() / 1000) - modified) : Infinity;
                const fresh = secondsAgo <= ACTIVE_WINDOW_SECONDS;
                if (fresh) setStatus('online', 'ESP32 Connected');
                else if (secondsAgo <= OFFLINE_WINDOW_SECONDS) {
                    setStatus('stale', 'ESP32 Possibly Disconnected (' + secondsAgo + 's ago)');
                } else {
                    setStatus('offline', 'ESP32 Offline (' + secondsAgo + 's ago)');
                }

                showSensorStates(data, fresh);
                storeNewReadings(data);

                // Show a current sensor-check warning, but keep reading cards visible.
                const failedCheck = fresh && data.system_ok === false;
                document.getElementById('warning-banner').style.display = failedCheck ? 'block' : 'none';
                if (failedCheck) document.getElementById('missing-list').textContent = data.missing || 'Unknown sensor';

                if (fresh) {
                    showNoReadingMessage('Waiting for valid sensor readings', 'The ESP32 is reporting data, but no complete readings are available yet.');
                } else {
                    // A stale placeholder is useful ONLY if there is no saved reading to show.
                    showNoReadingMessage('ESP32 telemetry is stale', 'No recent device update. Waiting for sensor readings.');
                }
            } catch (err) {
                setStatus('offline', 'Connection error');
                document.getElementById('warning-banner').style.display = 'none';
                if (hasReadings()) showSensorStates({}, false);
                showNoReadingMessage('Waiting for ESP32 data', 'Unable to retrieve new readings from the server.');
            } finally {
                polling = false;
            }
        }

        renderReadings();
        showReadingArea();
        poll();
        setInterval(poll, POLL_INTERVAL);
    </script>
</body>
</html>
