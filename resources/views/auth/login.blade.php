<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIDX — Softswitch Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box}
        :root{
          --bg:#f4f6fa; --surface:#ffffff; --surface2:#f8fafc;
          --border:#e2e8f0; --bordersoft:#edf2f7;
          --ink1:#0f172a; --ink2:#475569; --ink3:#94a3b8;
          --primary:#003875; --primary-dk:#002754; --primary-dim:rgba(0,56,117,.08);
          --accent:#ea5518; --accent-dim:rgba(234,85,24,.10);
          --danger:#dc2626; --danger-dim:rgba(220,38,38,.09);
          --ok:#059669; --ok-dim:rgba(5,150,105,.09);
          --disp:'Sora',sans-serif; --ui:'Inter',sans-serif; --mono:'JetBrains Mono',monospace;
          --r:8px; --rs:5px;
        }
        body{
            font-family:var(--ui);
            background: radial-gradient(circle at 10% 20%, rgba(0, 56, 117, 0.04) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(234, 85, 24, 0.04) 0%, transparent 40%),
                        var(--bg);
            color:var(--ink1);
            margin:0;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }
        .login-container{
            width:100%;
            max-width:400px;
            background:var(--surface);
            border:1px solid var(--border);
            border-radius:var(--r);
            box-shadow:0 8px 24px rgba(15,23,42,0.06);
            overflow:hidden;
        }
        .login-header{
            padding:34px 28px 20px;
            text-align:center;
            border-bottom:1px solid var(--bordersoft);
            background:var(--surface);
        }
        .logo-box{
            margin:0 auto 12px;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .title{
            font-family:var(--disp);
            font-size:18px;
            font-weight:700;
            color:var(--ink1);
            margin:0;
        }
        .subtitle{
            font-size:11px;
            color:var(--ink3);
            margin:4px 0 0;
            font-family:var(--mono);
            text-transform:uppercase;
            letter-spacing:1px;
        }
        .login-body{
            padding:26px 28px;
        }
        .form-group{
            margin-bottom:18px;
            position:relative;
        }
        .form-group label{
            display:block;
            font-size:10.5px;
            font-weight:700;
            text-transform:uppercase;
            color:var(--ink3);
            margin-bottom:6px;
            font-family:var(--mono);
            letter-spacing:0.5px;
        }
        .input-wrapper{
            position:relative;
        }
        .input-wrapper i{
            position:absolute;
            left:12px;
            top:50%;
            transform:translateY(-50%);
            color:var(--ink3);
            font-size:13px;
        }
        .form-input{
            width:100%;
            padding:10px 12px 10px 36px;
            border:1px solid var(--border);
            border-radius:var(--rs);
            background:var(--surface2);
            color:var(--ink1);
            font-size:13px;
            outline:none;
            font-family:var(--ui);
            transition:border-color .15s,box-shadow .15s;
        }
        .form-input:focus{
            border-color:var(--primary);
            background:#fff;
            box-shadow:0 0 0 3px var(--primary-dim);
        }
        .btn-submit{
            width:100%;
            padding:11px;
            background:var(--primary);
            color:#fff;
            border:none;
            border-radius:var(--rs);
            font-size:13px;
            font-weight:700;
            font-family:var(--ui);
            cursor:pointer;
            transition:all .15s;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            margin-top:10px;
        }
        .btn-submit:hover{
            background:var(--primary-dk);
        }
        .alert{
            padding:10px 12px;
            border-radius:var(--rs);
            font-size:12px;
            margin-bottom:18px;
            display:flex;
            align-items:center;
            gap:8px;
        }
        .alert-error{
            background:var(--danger-dim);
            color:var(--danger);
            border:1px solid rgba(220,38,38,.2);
        }
        .alert-success{
            background:var(--ok-dim);
            color:var(--ok);
            border:1px solid rgba(5,150,105,.2);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-box">
                <img src="{{ asset('images/didx-logo.svg') }}" alt="DIDX" style="height:38px;width:auto;display:block">
            </div>
            <h1 class="title">Softswitch Console</h1>
            <p class="subtitle">Authentication Portal</p>
        </div>
        <div class="login-body">
            @if($errors->any())
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="username" class="form-input" placeholder="Enter username" value="{{ old('username') }}" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" class="form-input" placeholder="Enter password" required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Console
                </button>
            </form>
        </div>
    </div>
</body>
</html>
