<html lang="{{ app()->getLocale() }}">
    <head>
        <title>{{ env('APP_NAME') }}</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta charset="utf-8" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script src="/js/jquery.min.js" type="text/javascript"></script>
        <script src="/js/app.js" type="text/javascript"></script>
        <script src="/js/jquery.dataTables.min.js" type="text/javascript"></script>
        <script src="/js/dataTables.bootstrap4.min.js" type="text/javascript"></script>
        <link rel="stylesheet" href="/css/font-awesome.min.css">
        <link rel="stylesheet" href="/css/bootstrap.min.css" />
        <link rel="stylesheet" href="/css/app.css" />
        @yield('styles')
    </head>
    <body>
        <div class="container-fluid">
            @include('nav')
            @if (session('error'))
                <div class="alert alert-danger" id="main-alert">
                    ERROR: {{ session('error') }}
                </div>
            @endif
            @if (session('info'))
                <div class="alert alert-info">
                    {{ session('info') }}
                </div>
            @endif
            <div class="alert alert-info" id="inline-info" style="display: none;"></div>
            <div class="alert alert-danger" id="inline-error" style="display: none;"></div>
            <div class="jumbotron text-white">
            @yield('content')
            </div>
        </div>
        <script type="text/javascript">
            function showInfo(msg, timeout = 3000)
            {
                let d = $("#inline-info");
                d.html(msg);
                d.fadeIn();
                setTimeout(() => d.fadeOut(), timeout);
            }

            function showError(msg, timeout = 3000)
            {
                let d = $("#inline-error");
                d.text("ERROR: " + msg);
                d.fadeIn();
                setTimeout(() => d.fadeOut(), timeout);
            }

            (function($){
                $.fn.serializeObject = function(){

                    var self = this,
                        json = {},
                        push_counters = {},
                        patterns = {
                            "validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
                            "key":      /[a-zA-Z0-9_]+|(?=\[\])/g,
                            "push":     /^$/,
                            "fixed":    /^\d+$/,
                            "named":    /^[a-zA-Z0-9_]+$/
                        };


                    this.build = function(base, key, value){
                        base[key] = value;
                        return base;
                    };

                    this.push_counter = function(key){
                        if(push_counters[key] === undefined){
                            push_counters[key] = 0;
                        }
                        return push_counters[key]++;
                    };

                    $.each($(this).serializeArray(), function(){

                        // skip invalid keys
                        if(!patterns.validate.test(this.name)){
                            return;
                        }

                        var k,
                            keys = this.name.match(patterns.key),
                            merge = this.value,
                            reverse_key = this.name;

                        while((k = keys.pop()) !== undefined){

                            // adjust reverse_key
                            reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

                            // push
                            if(k.match(patterns.push)){
                                merge = self.build([], self.push_counter(reverse_key), merge);
                            }

                            // fixed
                            else if(k.match(patterns.fixed)){
                                merge = self.build([], k, merge);
                            }

                            // named
                            else if(k.match(patterns.named)){
                                merge = self.build({}, k, merge);
                            }
                        }

                        json = $.extend(true, json, merge);
                    });

                    json._token = "{{ csrf_token() }}";

                    return json;
                };
            })(jQuery);
        </script>
        @yield('scripts')
    </body>
</html>