backend default {
	.host = "127.0.0.1";
	.port = "80";
	.first_byte_timeout = 300s;
}

/*
Like the default function, only that cookies don't prevent caching
*/
sub vcl_recv {
#	if (req.http.Host != "varnish.demo.aoemedia.de") {
#		return (pipe);
#	}

	# see http://www.varnish-cache.org/trac/wiki/VCLExampleNormalizeAcceptEncoding
	### parse accept encoding rulesets to normalize
	if (req.http.Accept-Encoding) {
		if (req.url ~ "\.(jpg|jpeg|png|gif|gz|tgz|bz2|tbz|mp3|ogg|swf|mp4|flv)$") {
			# don't try to compress already compressed files
			remove req.http.Accept-Encoding;
		} elsif (req.http.Accept-Encoding ~ "gzip") {
			set req.http.Accept-Encoding = "gzip";
		} elsif (req.http.Accept-Encoding ~ "deflate") {
			set req.http.Accept-Encoding = "deflate";
		} else {
			# unkown algorithm
			remove req.http.Accept-Encoding;
		}
	}

	if (req.http.x-forwarded-for) {
		set req.http.X-Forwarded-For =
		req.http.X-Forwarded-For ", " client.ip;
	} else {
		set req.http.X-Forwarded-For = client.ip;
	}
	if (req.request != "GET" &&
		req.request != "HEAD" &&
		req.request != "PUT" &&
		req.request != "POST" &&
		req.request != "TRACE" &&
		req.request != "OPTIONS" &&
		req.request != "DELETE") {
		/* Non-RFC2616 or CONNECT which is weird. */
		return (pipe);
	}

	# Some known-static file types
	if (req.url ~ "^[^?]*\.(css|js|htc|xml|txt|swf|flv|pdf|gif|jpe?g|png|ico)$") {
		# Pretent no cookie was passed
		unset req.http.Cookie;
	}

	# Force lookup if the request is a no-cache request from the client.
	if (req.http.Cache-Control ~ "no-cache") {
		purge_url(req.url);
	}

	if (req.request != "GET" && req.request != "HEAD") {
		/* We only deal with GET and HEAD by default */
		return (pass);
	}
	if (req.http.Authorization) {
		/* Not cacheable by default */
		return (pass);
	}
	return (lookup);
}

/*
Remove cookies from backend response so this page can be cached
*/
sub vcl_fetch {
	if (beresp.status == 302 || beresp.status == 301 || beresp.status == 418) {
		return (pass);
	}
	if (beresp.http.aoestatic == "cache") {
		remove beresp.http.Set-Cookie;
		remove beresp.http.X-Cache;
		remove beresp.http.Server;
		remove beresp.http.Age;
		remove beresp.http.Pragma;
		set beresp.http.Cache-Control = "public";
		set beresp.grace = 2m;
		set beresp.http.X_AOESTATIC_FETCH = "Removed cookie in vcl_fetch";
	} else {
		set beresp.http.X_AOESTATIC_FETCH = "Nothing removed";
	}

	# Some known-static file types
	if (req.url ~ "^[^?]*\.(css|js|htc|xml|txt|swf|flv|pdf|gif|jpe?g|png|ico)$") {
		# Force caching
		remove beresp.http.Pragma;
		remove beresp.http.Set-Cookie;
		set beresp.http.Cache-Control = "public";
	}

	if (!beresp.cacheable) {
		return (pass);
	}
	return (deliver);
}

/*
Adding debugging information
*/
sub vcl_deliver {
	if (obj.hits > 0) {
		set resp.http.X-Cache = "HIT";
		set resp.http.Server = "Varnish (HIT)";
	} else {
		set resp.http.X-Cache = "MISS";
		set resp.http.Server = "Varnish (MISS)";
	}
}