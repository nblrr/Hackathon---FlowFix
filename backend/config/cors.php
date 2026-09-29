<?php
return ['paths'=>['api/*'],'allowed_methods'=>['*'],'allowed_origins'=>[env('FRONTEND_URL','http://localhost:5173')],'allowed_origins_patterns'=>[],'allowed_headers'=>['Content-Type','Authorization','Accept'],'exposed_headers'=>['X-Request-ID'],'max_age'=>0,'supports_credentials'=>false];
