<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACU Advising</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet"> 
</head>
<body>
    <header id="header">
        <div class="container">
            <div class="row">
                <div class="col-md-2 header-title">
                    <h1>ADVISING</h1>
                </div>
                <div class="col-md-10 header-links">
                    <a href="cse_details.php">Courses</a>
                    <a href="register.php" style="margin-left: 20px">Apply</a>
                </div>
            </div>
        </div>
    </header>


    <section id="login-section">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="login-heading">Log In</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <form action="login.php" class="login-form" method="post">
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="text" id="username" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/wow.min.js"></script>
  </body> 
</body>
</html>
