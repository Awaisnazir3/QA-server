<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIDX — Console Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box}
        :root{
          --bg:#f3f4f9; --surface:#ffffff; --surface2:#f8f9fd; --hover:#eef0f9;
          --border:#e6e8f2; --bordersoft:#eef0f8;
          --ink1:#171a2c; --ink2:#5c6280; --ink3:#9499b3;
          --primary:#6153f6; --primary-dk:#4d3fe0; --primary-dim:rgba(97,83,246,.08); --primary-line:rgba(97,83,246,.35);
          --danger:#e0393f; --danger-dim:rgba(224,57,63,.09);
          --ok:#0fa66a; --ok-dim:rgba(15,166,106,.09);
          --disp:'Sora',sans-serif; --ui:'Inter',sans-serif; --mono:'JetBrains Mono',monospace;
          --r:14px; --rs:9px;
        }
        body{
            font-family:var(--ui);
            background: radial-gradient(circle at 10% 20%, rgba(97, 83, 246, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(97, 83, 246, 0.05) 0%, transparent 40%),
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
            max-width:420px;
            background:var(--surface);
            border:1px solid var(--border);
            border-radius:var(--r);
            box-shadow:0 12px 32px rgba(20,20,50,0.06);
            overflow:hidden;
            position:relative;
        }
        .login-header{
            padding:40px 30px 20px;
            text-align:center;
            border-bottom:1px solid var(--bordersoft);
        }
        .logo-box{
            width:48px;
            height:48px;
            border-radius:14px;
            background:linear-gradient(135deg,var(--primary),#8f6ffc);
            display:flex;
            align-items:center;
            justify-content:center;
            color:#fff;
            font-size:20px;
            box-shadow:0 6px 16px rgba(97,83,246,.35);
            margin:0 auto 16px;
        }
        .title{
            font-family:var(--disp);
            font-size:20px;
            font-weight:700;
            color:var(--ink1);
            margin:0;
        }
        .subtitle{
            font-size:12px;
            color:var(--ink3);
            margin:6px 0 0;
            font-family:var(--mono);
            text-transform:uppercase;
            letter-spacing:1px;
        }
        .login-body{
            padding:30px;
        }
        .form-group{
            margin-bottom:20px;
            position:relative;
        }
        .form-group label{
            display:block;
            font-size:11px;
            font-weight:700;
            text-transform:uppercase;
            color:var(--ink3);
            margin-bottom:8px;
            font-family:var(--mono);
            letter-spacing:0.5px;
        }
        .input-wrapper{
            position:relative;
        }
        .input-wrapper i{
            position:absolute;
            left:14px;
            top:50%;
            transform:translateY(-50%);
            color:var(--ink3);
            font-size:14px;
            transition:color .2s;
        }
        .form-input{
            width:100%;
            padding:12px 14px 12px 42px;
            border:1px solid var(--border);
            border-radius:var(--rs);
            background:var(--surface2);
            color:var(--ink1);
            font-family:var(--ui);
            font-size:13.5px;
            outline:none;
            transition:border-color .2s,box-shadow .2s,background .2s;
        }
        .form-input:focus{
            border-color:var(--primary);
            box-shadow:0 0 0 3px var(--primary-dim);
            background:var(--surface);
        }
        .form-input:focus + i{
            color:var(--primary);
        }
        .remember-forgot{
            display:flex;
            justify-content:space-between;
            align-items:center;
            font-size:12px;
            margin-bottom:24px;
        }
        .remember-me{
            display:flex;
            align-items:center;
            gap:8px;
            color:var(--ink2);
            cursor:pointer;
        }
        .btn-submit{
            width:100%;
            padding:12px;
            background:linear-gradient(135deg,var(--primary),#7a6bf9);
            color:#fff;
            border-radius:var(--rs);
            font-size:13.5px;
            font-weight:700;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            border:none;
            cursor:pointer;
            box-shadow:0 4px 12px rgba(97,83,246,.28);
            transition:transform .12s,box-shadow .12s;
        }
        .btn-submit:hover{
            transform:translateY(-1px);
            box-shadow:0 6px 16px rgba(97,83,246,.36);
        }
        .btn-submit:active{
            transform:translateY(0);
        }
        .alert{
            padding:12px 14px;
            border-radius:var(--rs);
            font-size:12.5px;
            font-weight:600;
            margin-bottom:20px;
        }
        .alert-error{
            background:var(--danger-dim);
            color:var(--danger);
            border:1px solid rgba(224,57,63,.15);
        }
        .alert-success{
            background:var(--ok-dim);
            color:var(--ok);
            border:1px solid rgba(15,166,106,.15);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-box"><i class="fa-solid fa-tower-broadcast"></i></div>
            <h1 class="title">Welcome to DIDX</h1>
            <p class="subtitle">Softswitch Control Console</p>
        </div>
        <div class="login-body">
            @if($errors->any())
                <div class="alert alert-error">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" value="{{ old('username') }}" required autofocus>
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember" style="accent-color:var(--primary)">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    Sign In <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
