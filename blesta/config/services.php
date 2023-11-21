<?php
// The services to load to the container
// Order may matter here
return [
    'Blesta\\Core\\ServiceProviders\\Logger',
    'Blesta\\Core\\ServiceProviders\\MinphpBridge',
    'Blesta\\Core\\ServiceProviders\\Pagination',
    'Blesta\\Core\\ServiceProviders\\Pricing',
    'Blesta\\Core\\ServiceProviders\\Requestor',
    'Blesta\\Core\\ServiceProviders\\Util',
    'Blesta\\Core\\ServiceProviders\\App',
];
