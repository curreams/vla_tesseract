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
        <div class="wrapper row3">
            <main class="hoc container clear"> 
                <div class="sectiontitle">
                <h6 class="heading">Possible Clients</h6>
                <p>Select one of the clients from the list.</p>
                </div>
                <div class="group excerpts container">
                    <div class="row">
                        @foreach($possibleClients as $key => $client)
                        <div class="col-md-3" >
                            <article class="one_third half" >
                                <div class="hgroup">
                                <h6 class="heading">{!! $client->ClientName !!}</h6>
                                <em>{!! $client->DOB !!}</em></div>
                                <div class="txtwrap">
                                <p>{!! isset($client->HomeAddress) && !empty($client->HomeAddress) ? $client->HomeAddress : ' No Home Address Provided' !!}</p>
                                </div>
                            </article>
                        </div>
                        @endforeach
                </div>
                </div>
                <div class="clear"></div>
            </main>
        </div>
        <section id="pageintro" class="hoc clear">
            <div class="container">            
                <div class="row">
                    @foreach($result as $key => $page)
                    <div class="col-md-12 custom-text-box" >
                        <h2>Page {{ $key + 1  }}</h2>
                        <p>{!! $page !!}</p>
                    </div>
                    @endforeach
                </div >
            </div>
        </section>
    </div>
        <!-- JAVASCRIPTS -->
        <script src="js/jquery.min.js"></script>
        <script src="js/jquery.backtotop.js"></script>
        <script src="js/jquery.mobilemenu.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    </body>
</html>
