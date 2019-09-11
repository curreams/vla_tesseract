<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>VLA OCR</title>

        <!-- Fonts -->
        <link href="css/custom.css" rel="stylesheet" type="text/css" media="all">

    </head>
    <body id="top">

    <div class="bgded overlay" id="main_billboard">
        <div class="wrapper row1">
            <header id="header" class="hoc clear"> 
                <div id="logo" class="fl_left">
                    <h1><a href="{{ url('/') }}">VLA OCR</a></h1>
                </div>
            </header>
        </div>
        <section id="pageintro" class="hoc clear">
            <div>
                <form method="POST" action="/parseDocument" class="form-horizontal" enctype="multipart/form-data" >
                    {{ csrf_field() }} 
                    <h2 class="heading">Upload File</h2>                    
                    
                    <input type="file" name="upload_file" id="upload_file" accept="application/pdf" required>
                    
                    <footer><button type="submit" class="btn btn-circle green">Parse File</button></footer>
                </form>
            </div>
        </section>
    </div>
        <!-- JAVASCRIPTS -->
        <script src="js/jquery.min.js"></script>
        <script src="js/jquery.backtotop.js"></script>
        <script src="js/jquery.mobilemenu.js"></script>
    </body>
</html>
