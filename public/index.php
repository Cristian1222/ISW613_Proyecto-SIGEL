
<body>
    <div class="container mt-5">
        <h1 class="text-center">Proyecto SIGEL</h1>
        <p class="text-center">
            This is a simple homepage created with PHP and Bootstrap.
        </p>
    </div>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">

                    <div class="card-header text-center">
                        <h4>Login</h4>
                    </div>

                    <div class="card-body">

                        <?php if (isset($_GET['registered'])): ?>
                            <div class="alert alert-success">
                                Usuario registrado correctamente.
                                Ya puedes iniciar sesión.
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                No se pudo iniciar sesión.
                                Revisa los datos ingresados.
                            </div>
                        <?php endif; ?>

                        <form action="actions/login.php" method="POST">

                            <div class="form-group">
                                <label for="username">Username</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="username"
                                    name="username"
                                    maxlength="50"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    required
                                >
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary btn-block"
                            >
                                Login
                            </button>
                        </form>

                        <p class="text-center mt-3">
                            ¿No tienes una cuenta?
                            <a href="pages/register.php">Registrarse</a>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>

