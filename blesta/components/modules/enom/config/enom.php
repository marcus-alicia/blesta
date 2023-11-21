<?php
// All available TLDs
Configure::set('Enom.tlds', [
    // Second-Level
    '.br.com',
    '.cn.com',
    '.com.au',
    '.com.de',
    '.com.es',
    '.com.pe',
    '.com.sg',
    '.de.com',
    '.eu.com',
    '.gr.com',
    '.hu.com',
    '.net.au',
    '.net.pe',
    '.no.com',
    '.nom.es',
    '.nom.pe',
    '.org.au',
    '.org.es',
    '.org.pe',
    '.qc.com',
    '.ru.com',
    '.sq.com',
    '.se.com',
    '.uk.com',
    '.us.com',
    '.us.org',
    '.uy.com',
    '.za.com',
    '.com.mx',
    '.uk.net',
    '.se.net',
    '.co.uk',
    // Generic
    '.biz',
    '.com',
    '.info',
    '.name',
    '.net',
    '.org',
    '.xxx',
    // Speciality
    '.asia',
    '.cm',
    '.co',
    '.me',
    '.mobi',
    '.pro',
    '.pw',
    '.tel',
    '.tv',
    // Country Code
    '.ac',
    '.am',
    '.at',
    '.be',
    '.bz',
    '.ca',
    '.cc',
    '.ch',
    '.cn',
    '.de',
    '.es',
    '.eu',
    '.fm',
    '.fr',
    '.gs',
    '.in',
    '.io',
    '.it',
    '.jp',
    '.la',
    '.li',
    '.ms',
    '.nl',
    '.nz',
    '.pe',
    '.sg',
    '.sh',
    '.tc',
    '.tm',
    '.tw',
    '.us',
    '.uk',
    '.vg',
    '.ws',
    // Other
    '.abogado',
    '.academy',
    '.accountant',
    '.accountants',
    '.actor',
    '.adult',
    '.agency',
    '.airforce',
    '.radio.am',
    '.apartments',
    '.app',
    '.archi',
    '.army',
    '.art',
    '.associates',
    '.attorney',
    '.auction',
    '.audio',
    '.auto',
    '.baby',
    '.band',
    '.bar',
    '.barcelona',
    '.bargains',
    '.bayern',
    '.beauty',
    '.beer',
    '.berlin',
    '.best',
    '.bet',
    '.bible',
    '.bid',
    '.bike',
    '.bingo',
    '.bio',
    '.black',
    '.blackfriday',
    '.blog',
    '.blue',
    '.boston',
    '.boutique',
    '.build',
    '.builders',
    '.business',
    '.buzz',
    '.cab',
    '.cafe',
    '.cam',
    '.camera',
    '.camp',
    '.capital',
    '.car',
    '.cards',
    '.care',
    '.careers',
    '.cars',
    '.casa',
    '.cash',
    '.casino',
    '.catering',
    '.center',
    '.ceo',
    '.charity',
    '.chat',
    '.cheap',
    '.christmas',
    '.church',
    '.city',
    '.claims',
    '.cleaning',
    '.click',
    '.clinic',
    '.clothing',
    '.cloud',
    '.club',
    '.com.cn',
    '.net.cn',
    '.org.cn',
    '.com.co',
    '.net.co',
    '.nom.co',
    '.coach',
    '.codes',
    '.coffee',
    '.college',
    '.ar.com',
    '.co.com',
    '.jpn.com',
    '.sa.com',
    '.community',
    '.company',
    '.computer',
    '.condos',
    '.construction',
    '.consulting',
    '.contractors',
    '.cooking',
    '.cool',
    '.country',
    '.coupons',
    '.courses',
    '.credit',
    '.creditcard',
    '.cricket',
    '.cruises',
    '.cymru',
    '.dance',
    '.date',
    '.dating',
    '.deals',
    '.degree',
    '.delivery',
    '.democrat',
    '.dental',
    '.dentist',
    '.desi',
    '.design',
    '.dev',
    '.diamonds',
    '.diet',
    '.digital',
    '.direct',
    '.directory',
    '.discount',
    '.doctor',
    '.dog',
    '.domains',
    '.download',
    '.earth',
    '.education',
    '.email',
    '.energy',
    '.engineer',
    '.engineering',
    '.enterprises',
    '.equipment',
    '.estate',
    '.eus',
    '.events',
    '.exchange',
    '.expert',
    '.exposed',
    '.express',
    '.fail',
    '.faith',
    '.family',
    '.fans',
    '.farm',
    '.fashion',
    '.film',
    '.finance',
    '.financial',
    '.fish',
    '.fishing',
    '.fit',
    '.fitness',
    '.flights',
    '.florist',
    '.flowers',
    '.radio.fm',
    '.football',
    '.forsale',
    '.foundation',
    '.fun',
    '.fund',
    '.furniture',
    '.futbol',
    '.fyi',
    '.gal',
    '.gallery',
    '.game',
    '.games',
    '.garden',
    '.gay',
    '.gift',
    '.gifts',
    '.gives',
    '.glass',
    '.global',
    '.gmbh',
    '.gold',
    '.golf',
    '.graphics',
    '.gratis',
    '.green',
    '.gripe',
    '.group',
    '.guide',
    '.guitars',
    '.guru',
    '.hair',
    '.hamburg',
    '.haus',
    '.healthcare',
    '.help',
    '.hiphop',
    '.hockey',
    '.holdings',
    '.holiday',
    '.horse',
    '.hospital',
    '.host',
    '.hosting',
    '.house',
    '.how',
    '.immo',
    '.immobilien',
    '.inc',
    '.industries',
    '.ink',
    '.institute',
    '.insure',
    '.international',
    '.investments',
    '.irish',
    '.jetzt',
    '.jewelry',
    '.juegos',
    '.kaufen',
    '.kim',
    '.kitchen',
    '.kiwi',
    '.land',
    '.law',
    '.lawyer',
    '.lease',
    '.legal',
    '.lgbt',
    '.life',
    '.lighting',
    '.limited',
    '.limo',
    '.link',
    '.live',
    '.loan',
    '.loans',
    '.lol',
    '.london',
    '.love',
    '.ltd',
    '.ltda',
    '.luxury',
    '.maison',
    '.makeup',
    '.management',
    '.market',
    '.marketing',
    '.mba',
    '.media',
    '.melbourne',
    '.memorial',
    '.men',
    '.menu',
    '.miami',
    '.moda',
    '.moe',
    '.mom',
    '.money',
    '.monster',
    '.mortgage',
    '.movie',
    '.nagoya',
    '.navy',
    '.network',
    '.news',
    '.ngo',
    '.ninja',
    '.nu',
    '.nyc',
    '.co.nz',
    '.net.nz',
    '.org.nz',
    '.okinawa',
    '.one',
    '.ong',
    '.onl',
    '.online',
    '.ooo',
    '.osaka',
    '.page',
    '.paris',
    '.partners',
    '.parts',
    '.party',
    '.pet',
    '.photo',
    '.photography',
    '.photos',
    '.physio',
    '.pics',
    '.pictures',
    '.pink',
    '.pizza',
    '.place',
    '.plumbing',
    '.plus',
    '.poker',
    '.porn',
    '.press',
    '.aaa.pro',
    '.aca.pro',
    '.acct.pro',
    '.avocat.pro',
    '.bar.pro',
    '.cpa.pro',
    '.eng.pro',
    '.jur.pro',
    '.law.pro',
    '.med.pro',
    '.recht.pro',
    '.productions',
    '.promo',
    '.properties',
    '.property',
    '.protection',
    '.pub',
    '.qpon',
    '.quebec',
    '.quest',
    '.racing',
    '.recipes',
    '.red',
    '.rehab',
    '.reise',
    '.reisen',
    '.rent',
    '.rentals',
    '.repair',
    '.report',
    '.republican',
    '.rest',
    '.restaurant',
    '.review',
    '.reviews',
    '.rich',
    '.rip',
    '.rocks',
    '.rodeo',
    '.run',
    '.ryukyu',
    '.sale',
    '.salon',
    '.sarl',
    '.school',
    '.schule',
    '.science',
    '.scot',
    '.security',
    '.services',
    '.sex',
    '.sexy',
    '.shiksha',
    '.shoes',
    '.shop',
    '.shopping',
    '.show',
    '.singles',
    '.site',
    '.ski',
    '.skin',
    '.soccer',
    '.social',
    '.software',
    '.solar',
    '.solutions',
    '.soy',
    '.space',
    '.srl',
    '.store',
    '.stream',
    '.studio',
    '.study',
    '.style',
    '.sucks',
    '.supplies',
    '.supply',
    '.support',
    '.surf',
    '.surgery',
    '.sydney',
    '.systems',
    '.tattoo',
    '.tax',
    '.taxi',
    '.team',
    '.tech',
    '.technology',
    '.tennis',
    '.theater',
    '.theatre',
    '.tienda',
    '.tips',
    '.tires',
    '.today',
    '.tokyo',
    '.tools',
    '.top',
    '.tours',
    '.town',
    '.toys',
    '.trade',
    '.training',
    '.tube',
    '.com.tw',
    '.idv.tw',
    '.org.tw',
    '.me.uk',
    '.org.uk',
    '.university',
    '.uno',
    '.vacations',
    '.vegas',
    '.ventures',
    '.vet',
    '.viajes',
    '.video',
    '.villas',
    '.vin',
    '.vip',
    '.vision',
    '.vodka',
    '.vote',
    '.voting',
    '.voto',
    '.voyage',
    '.wales',
    '.watch',
    '.webcam',
    '.website',
    '.wedding',
    '.wiki',
    '.win',
    '.wine',
    '.work',
    '.works',
    '.world',
    '.wtf',
    '.xyz',
    '.yoga',
    '.yokohama',
    '.zone',
]);

// Transfer fields
Configure::set('Enom.transfer_fields', [
    'domain' => [
        'label' => Language::_('Enom.transfer.domain', true),
        'type' => 'text'
    ],
    'transfer_key' => [
        'label' => Language::_('Enom.transfer.transfer_key', true),
        'type' => 'text'
    ]
]);

// Domain fields
Configure::set('Enom.domain_fields', [
    'domain' => [
        'label' => Language::_('Enom.domain.domain', true),
        'type' => 'text'
    ],
]);

// Nameserver fields
Configure::set('Enom.nameserver_fields', [
    'ns1' => [
        'label' => Language::_('Enom.nameserver.ns1', true),
        'type' => 'text'
    ],
    'ns2' => [
        'label' => Language::_('Enom.nameserver.ns2', true),
        'type' => 'text'
    ],
    'ns3' => [
        'label' => Language::_('Enom.nameserver.ns3', true),
        'type' => 'text'
    ],
    'ns4' => [
        'label' => Language::_('Enom.nameserver.ns4', true),
        'type' => 'text'
    ],
    'ns5' => [
        'label' => Language::_('Enom.nameserver.ns5', true),
        'type' => 'text'
    ]
]);

// Whois sections
Configure::set('Enom.whois_sections', [
    'Registrant',
    'AuxBilling',
    'Tech',
    'Admin',
    'Billing'
]);

// Whois fields
Configure::set('Enom.whois_fields', [
    'RegistrantFirstName' => [
        'label' => Language::_('Enom.whois.RegistrantFirstName', true),
        'type' => 'text'
    ],
    'RegistrantLastName' => [
        'label' => Language::_('Enom.whois.RegistrantLastName', true),
        'type' => 'text'
    ],
    'RegistrantAddress1' => [
        'label' => Language::_('Enom.whois.RegistrantAddress1', true),
        'type' => 'text'
    ],
    'RegistrantAddress2' => [
        'label' => Language::_('Enom.whois.RegistrantAddress2', true),
        'type' => 'text'
    ],
    'RegistrantCity' => [
        'label' => Language::_('Enom.whois.RegistrantCity', true),
        'type' => 'text'
    ],
    'RegistrantStateProvince' => [
        'label' => Language::_('Enom.whois.RegistrantStateProvince', true),
        'type' => 'text'
    ],
    'RegistrantPostalCode' => [
        'label' => Language::_('Enom.whois.RegistrantPostalCode', true),
        'type' => 'text'
    ],
    'RegistrantCountry' => [
        'label' => Language::_('Enom.whois.RegistrantCountry', true),
        'type' => 'text'
    ],
    'RegistrantPhone' => [
        'label' => Language::_('Enom.whois.RegistrantPhone', true),
        'type' => 'text'
    ],
    'RegistrantEmailAddress' => [
        'label' => Language::_('Enom.whois.RegistrantEmailAddress', true),
        'type' => 'text'
    ],
    'TechFirstName' => [
        'label' => Language::_('Enom.whois.TechFirstName', true),
        'type' => 'text'
    ],
    'TechLastName' => [
        'label' => Language::_('Enom.whois.TechLastName', true),
        'type' => 'text'
    ],
    'TechAddress1' => [
        'label' => Language::_('Enom.whois.TechAddress1', true),
        'type' => 'text'
    ],
    'TechAddress2' => [
        'label' => Language::_('Enom.whois.TechAddress2', true),
        'type' => 'text'
    ],
    'TechCity' => [
        'label' => Language::_('Enom.whois.TechCity', true),
        'type' => 'text'
    ],
    'TechStateProvince' => [
        'label' => Language::_('Enom.whois.TechStateProvince', true),
        'type' => 'text'
    ],
    'TechPostalCode' => [
        'label' => Language::_('Enom.whois.TechPostalCode', true),
        'type' => 'text'
    ],
    'TechCountry' => [
        'label' => Language::_('Enom.whois.TechCountry', true),
        'type' => 'text'
    ],
    'TechPhone' => [
        'label' => Language::_('Enom.whois.TechPhone', true),
        'type' => 'text'
    ],
    'TechEmailAddress' => [
        'label' => Language::_('Enom.whois.TechEmailAddress', true),
        'type' => 'text'
    ],
    'AdminFirstName' => [
        'label' => Language::_('Enom.whois.AdminFirstName', true),
        'type' => 'text'
    ],
    'AdminLastName' => [
        'label' => Language::_('Enom.whois.AdminLastName', true),
        'type' => 'text'
    ],
    'AdminAddress1' => [
        'label' => Language::_('Enom.whois.AdminAddress1', true),
        'type' => 'text'
    ],
    'AdminAddress2' => [
        'label' => Language::_('Enom.whois.AdminAddress2', true),
        'type' => 'text'
    ],
    'AdminCity' => [
        'label' => Language::_('Enom.whois.AdminCity', true),
        'type' => 'text'
    ],
    'AdminStateProvince' => [
        'label' => Language::_('Enom.whois.AdminStateProvince', true),
        'type' => 'text'
    ],
    'AdminPostalCode' => [
        'label' => Language::_('Enom.whois.AdminPostalCode', true),
        'type' => 'text'
    ],
    'AdminCountry' => [
        'label' => Language::_('Enom.whois.AdminCountry', true),
        'type' => 'text'
    ],
    'AdminPhone' => [
        'label' => Language::_('Enom.whois.AdminPhone', true),
        'type' => 'text'
    ],
    'AdminEmailAddress' => [
        'label' => Language::_('Enom.whois.AdminEmailAddress', true),
        'type' => 'text'
    ],
    'AuxBillingFirstName' => [
        'label' => Language::_('Enom.whois.AuxBillingFirstName', true),
        'type' => 'text'
    ],
    'AuxBillingLastName' => [
        'label' => Language::_('Enom.whois.AuxBillingLastName', true),
        'type' => 'text'
    ],
    'AuxBillingAddress1' => [
        'label' => Language::_('Enom.whois.AuxBillingAddress1', true),
        'type' => 'text'
    ],
    'AuxBillingAddress2' => [
        'label' => Language::_('Enom.whois.AuxBillingAddress2', true),
        'type' => 'text'
    ],
    'AuxBillingCity' => [
        'label' => Language::_('Enom.whois.AuxBillingCity', true),
        'type' => 'text'
    ],
    'AuxBillingStateProvince' => [
        'label' => Language::_('Enom.whois.AuxBillingStateProvince', true),
        'type' => 'text'
    ],
    'AuxBillingPostalCode' => [
        'label' => Language::_('Enom.whois.AuxBillingPostalCode', true),
        'type' => 'text'
    ],
    'AuxBillingCountry' => [
        'label' => Language::_('Enom.whois.AuxBillingCountry', true),
        'type' => 'text'
    ],
    'AuxBillingPhone' => [
        'label' => Language::_('Enom.whois.AuxBillingPhone', true),
        'type' => 'text'
    ],
    'AuxBillingEmailAddress' => [
        'label' => Language::_('Enom.whois.AuxBillingEmailAddress', true),
        'type' => 'text'
    ]
]);

// .US
Configure::set('Enom.domain_fields.us', [
    'us_nexus' => [
        'label' => Language::_('Enom.domain.RegistrantNexus', true),
        'type' => 'select',
        'options' => [
            'C11' => Language::_('Enom.domain.RegistrantNexus.c11', true),
            'C12' => Language::_('Enom.domain.RegistrantNexus.c12', true),
            'C21' => Language::_('Enom.domain.RegistrantNexus.c21', true),
            'C31' => Language::_('Enom.domain.RegistrantNexus.c31', true),
            'C32' => Language::_('Enom.domain.RegistrantNexus.c32', true)
        ]
    ],
    'us_purpose' => [
        'label' => Language::_('Enom.domain.RegistrantPurpose', true),
        'type' => 'select',
        'options' => [
            'P1' => Language::_('Enom.domain.RegistrantPurpose.p1', true),
            'P2' => Language::_('Enom.domain.RegistrantPurpose.p2', true),
            'P3' => Language::_('Enom.domain.RegistrantPurpose.p3', true),
            'P4' => Language::_('Enom.domain.RegistrantPurpose.p4', true),
            'P5' => Language::_('Enom.domain.RegistrantPurpose.p5', true)
        ]
    ]
]);

// .EU
Configure::set('Enom.domain_fields.eu', [
    'eu_whoispolicy' => [
        'label' => Language::_('Enom.domain.EUAgreeWhoisPolicy', true),
        'type' => 'checkbox',
        'options' => [
            'I AGREE' => Language::_('Enom.domain.EUAgreeWhoisPolicy.yes', true)
        ]
    ]
]);

// .CA
Configure::set('Enom.domain_fields.ca', [
    'cira_legal_type' => [
        'label' => Language::_('Enom.domain.CIRALegalType', true),
        'type' => 'select',
        'options' => [
            'CCO' => Language::_('Enom.domain.RegistrantPurpose.cco', true),
            'CCT' => Language::_('Enom.domain.RegistrantPurpose.cct', true),
            'RES' => Language::_('Enom.domain.RegistrantPurpose.res', true),
            'GOV' => Language::_('Enom.domain.RegistrantPurpose.gov', true),
            'EDU' => Language::_('Enom.domain.RegistrantPurpose.edu', true),
            'ASS' => Language::_('Enom.domain.RegistrantPurpose.ass', true),
            'HOP' => Language::_('Enom.domain.RegistrantPurpose.hop', true),
            'PRT' => Language::_('Enom.domain.RegistrantPurpose.prt', true),
            'TDM' => Language::_('Enom.domain.RegistrantPurpose.tdm', true),
            'TRD' => Language::_('Enom.domain.RegistrantPurpose.trd', true),
            'PLT' => Language::_('Enom.domain.RegistrantPurpose.plt', true),
            'LAM' => Language::_('Enom.domain.RegistrantPurpose.lam', true),
            'TRS' => Language::_('Enom.domain.RegistrantPurpose.trs', true),
            'ABO' => Language::_('Enom.domain.RegistrantPurpose.abo', true),
            'INB' => Language::_('Enom.domain.RegistrantPurpose.inb', true),
            'LGR' => Language::_('Enom.domain.RegistrantPurpose.lgr', true),
            'OMK' => Language::_('Enom.domain.RegistrantPurpose.omk', true),
            'MAJ' => Language::_('Enom.domain.RegistrantPurpose.maj', true)
        ]
    ],
    'cira_whois_display' => [
        'label' => Language::_('Enom.domain.CIRAWhoisDisplay', true),
        'type' => 'select',
        'options' => [
            'FULL' => Language::_('Enom.domain.CIRAWhoisDisplay.full', true),
            'PRIVATE' => Language::_('Enom.domain.CIRAWhoisDisplay.private', true),
        ]
    ],
    'cira_language' => [
        'label' => Language::_('Enom.domain.CIRALanguage', true),
        'type' => 'select',
        'options' => [
            'en' => Language::_('Enom.domain.CIRALanguage.en', true),
            'fr' => Language::_('Enom.domain.CIRALanguage.fr', true),
        ]
    ],
    'cira_agreement_version' => [
        'type' => 'hidden',
        'options' => '2.0'
    ],
    'cira_agreement_value' => [
        'type' => 'hidden',
        'options' => 'Y'
    ]
]);

// .UK
Configure::set('Enom.domain_fields.uk', [
    'uk_legal_type' => [
        'label' => Language::_('Enom.domain.UKLegalType', true),
        'type' => 'select',
        'options' => [
            'IND' => Language::_('Enom.domain.UKLegalType.ind', true),
            'FIND' => Language::_('Enom.domain.UKLegalType.find', true),
            'LTD' => Language::_('Enom.domain.UKLegalType.ltd', true),
            'PLC' => Language::_('Enom.domain.UKLegalType.plc', true),
            'PTNR' => Language::_('Enom.domain.UKLegalType.ptnr', true),
            'LLP' => Language::_('Enom.domain.UKLegalType.llp', true),
            'IP' => Language::_('Enom.domain.UKLegalType.ip', true),
            'STRA' => Language::_('Enom.domain.UKLegalType.stra', true),
            'SCH' => Language::_('Enom.domain.UKLegalType.sch', true),
            'RCHAR' => Language::_('Enom.domain.UKLegalType.rchar', true),
            'GOV' => Language::_('Enom.domain.UKLegalType.gov', true),
            'OTHER' => Language::_('Enom.domain.UKLegalType.other', true),
            'CRC' => Language::_('Enom.domain.UKLegalType.crc', true),
            'FCORP' => Language::_('Enom.domain.UKLegalType.fcorp', true),
            'STAT' => Language::_('Enom.domain.UKLegalType.stat', true),
            'FOTHER' => Language::_('Enom.domain.UKLegalType.fother', true)
        ]
    ],
    'uk_reg_co_no' => [
        'label' => Language::_('Enom.domain.UKCompanyID', true),
        'type' => 'text'
    ],
    'registered_for' => [
        'label' => Language::_('Enom.domain.UKRegisteredfor', true),
        'type' => 'text'
    ]
]);

// .ASIA
Configure::set('Enom.domain_fields.asia', [
    'asia_cclocality' => [
        'type' => 'hidden',
        'options' => null
    ],
    'asia_legalentitytype' => [
        'label' => Language::_('Enom.domain.ASIALegalEntityType', true),
        'type' => 'select',
        'options' => [
            'corporation' => Language::_('Enom.domain.ASIALegalEntityType.corporation', true),
            'cooperative' => Language::_('Enom.domain.ASIALegalEntityType.cooperative', true),
            'partnership' => Language::_('Enom.domain.ASIALegalEntityType.partnership', true),
            'government' => Language::_('Enom.domain.ASIALegalEntityType.government', true),
            'politicalParty' => Language::_('Enom.domain.ASIALegalEntityType.politicalParty', true),
            'society' => Language::_('Enom.domain.ASIALegalEntityType.society', true),
            'institution' => Language::_('Enom.domain.ASIALegalEntityType.institution', true),
            'naturalPerson' => Language::_('Enom.domain.ASIALegalEntityType.naturalPerson', true)
        ]
    ],
    'asia_identform' => [
        'label' => Language::_('Enom.domain.ASIAIdentForm', true),
        'type' => 'select',
        'options' => [
            'certificate' => Language::_('Enom.domain.ASIAIdentForm.certificate', true),
            'legislation' => Language::_('Enom.domain.ASIAIdentForm.legislation', true),
            'societyRegistry' => Language::_('Enom.domain.ASIAIdentForm.societyRegistry', true),
            'politicalPartyRegistry' => Language::_('Enom.domain.ASIAIdentForm.politicalPartyRegistry', true),
            'passport' => Language::_('Enom.domain.ASIAIdentForm.passport', true)
        ]
    ],
    'asia_ident:number' => [
        'label' => Language::_('Enom.domain.ASIAIdentNumber', true),
        'type' => 'text'
    ]
]);

// Email templates
Configure::set('Enom.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Your new domain is being processed and will be registered soon!

Domain: {service.domain}

Thank you for your business!',
        'html' => '<p>Your new domain is being processed and will be registered soon!</p>
<p>Domain: {service.domain}</p>
<p>Thank you for your business!</p>'
    ]
]);
