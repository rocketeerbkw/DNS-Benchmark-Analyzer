<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title>DNS Benchmark Analyzer</title>
        
        <link rel="stylesheet" href="styles_min.css" />
        
        <script type="text/javascript">
            function disableForm() {
                var button = document.getElementById('submit');
                button.disabled = 'disabled';
                return true;
            }
            
            // Google Analytics
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', '<?php echo $googleAnalyticsCode ?>']);
            _gaq.push(['_trackPageview']);

            (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
            })();
        </script>
        
    </head>
    <body>
        <h1>DNS Benchmark Analyzer</h1>
        <p>This site is designed to import multiple result sets from <a href="http://www.grc.com/dns/benchmark.htm" target="_blank">DNS Benchmark</a> to give you a better idea of longterm DNS server statistics.</p>