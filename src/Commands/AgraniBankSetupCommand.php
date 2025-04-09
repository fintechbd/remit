<?php

namespace Fintech\Remit\Commands;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Console\Command;
use Throwable;

class AgraniBankSetupCommand extends Command
{
    // comment are agrani bank doc serial

    const COUNTRY_CODES = [
        ['name' => 'Afghanistan', 'iso3' => 'AFG', 'revised_code' => 'AF'],
        ['name' => 'Aland Islands', 'iso3' => 'ALA', 'revised_code' => 'AX'],
        ['name' => 'Albania', 'iso3' => 'ALB', 'revised_code' => 'AL'],
        ['name' => 'Algeria', 'iso3' => 'DZA', 'revised_code' => 'DZ'],
        ['name' => 'American Samoa', 'iso3' => 'ASM', 'revised_code' => 'AS'],
        ['name' => 'Andorra', 'iso3' => 'AND', 'revised_code' => 'AD'],
        ['name' => 'Angola', 'iso3' => 'AGO', 'revised_code' => 'AO'],
        ['name' => 'Anguilla', 'iso3' => 'AIA', 'revised_code' => 'AI'],
        ['name' => 'Antarctica', 'iso3' => 'ATA', 'revised_code' => 'AQ'],
        ['name' => 'Antigua And Barbuda', 'iso3' => 'ATG', 'revised_code' => 'AG'],
        ['name' => 'Argentina', 'iso3' => 'ARG', 'revised_code' => 'AR'],
        ['name' => 'Armenia', 'iso3' => 'ARM', 'revised_code' => 'AM'],
        ['name' => 'Aruba', 'iso3' => 'ABW', 'revised_code' => 'AW'],
        ['name' => 'Australia', 'iso3' => 'AUS', 'revised_code' => 'AU'], // 3
        ['name' => 'Austria', 'iso3' => 'AUT', 'revised_code' => 'AT'],
        ['name' => 'Azerbaijan', 'iso3' => 'AZE', 'revised_code' => 'AZ'],
        ['name' => 'Bahrain', 'iso3' => 'BHR', 'revised_code' => 'BH'],
        ['name' => 'Bangladesh', 'iso3' => 'BGD', 'revised_code' => 'BD'], // 6
        ['name' => 'Barbados', 'iso3' => 'BRB', 'revised_code' => 'BB'],
        ['name' => 'Belarus', 'iso3' => 'BLR', 'revised_code' => 'BY'],
        ['name' => 'Belgium', 'iso3' => 'BEL', 'revised_code' => 'BE'],
        ['name' => 'Belize', 'iso3' => 'BLZ', 'revised_code' => 'BZ'],
        ['name' => 'Benin', 'iso3' => 'BEN', 'revised_code' => 'BJ'],
        ['name' => 'Bermuda', 'iso3' => 'BMU', 'revised_code' => 'BM'],
        ['name' => 'Bhutan', 'iso3' => 'BTN', 'revised_code' => 'BT'],
        ['name' => 'Bolivia', 'iso3' => 'BOL', 'revised_code' => 'BO'],
        ['name' => 'Bonaire, Sint Eustatius and Saba', 'iso3' => 'BES', 'revised_code' => 'BQ'],
        ['name' => 'Bosnia and Herzegovina', 'iso3' => 'BIH', 'revised_code' => 'BA'],
        ['name' => 'Botswana', 'iso3' => 'BWA', 'revised_code' => 'BW'], // 7
        ['name' => 'Bouvet Island', 'iso3' => 'BVT', 'revised_code' => 'BV'],
        ['name' => 'Brazil', 'iso3' => 'BRA', 'revised_code' => 'BR'],
        ['name' => 'British Indian Ocean Territory', 'iso3' => 'IOT', 'revised_code' => 'IO'],
        ['name' => 'Brunei', 'iso3' => 'BRN', 'revised_code' => 'BN'],
        ['name' => 'Bulgaria', 'iso3' => 'BGR', 'revised_code' => 'BG'],
        ['name' => 'Burkina Faso', 'iso3' => 'BFA', 'revised_code' => 'BF'],
        ['name' => 'Burundi', 'iso3' => 'BDI', 'revised_code' => 'BI'],
        ['name' => 'Cambodia', 'iso3' => 'KHM', 'revised_code' => 'KH'],
        ['name' => 'Cameroon', 'iso3' => 'CMR', 'revised_code' => 'CM'],
        ['name' => 'Canada', 'iso3' => 'CAN', 'revised_code' => 'CA'], // 8
        ['name' => 'Cape Verde', 'iso3' => 'CPV', 'revised_code' => 'CV'],
        ['name' => 'Cayman Islands', 'iso3' => 'CYM', 'revised_code' => 'KY'],
        ['name' => 'Central African Republic', 'iso3' => 'CAF', 'revised_code' => 'CF'],
        ['name' => 'Chad', 'iso3' => 'TCD', 'revised_code' => 'TD'],
        ['name' => 'Chile', 'iso3' => 'CHL', 'revised_code' => 'CL'],
        ['name' => 'China', 'iso3' => 'CHN', 'revised_code' => 'CN'], // 10
        ['name' => 'Christmas Island', 'iso3' => 'CXR', 'revised_code' => 'CX'],
        ['name' => 'Cocos (Keeling) Islands', 'iso3' => 'CCK', 'revised_code' => 'CC'],
        ['name' => 'Colombia', 'iso3' => 'COL', 'revised_code' => 'BC'], // 5
        ['name' => 'Comoros', 'iso3' => 'COM', 'revised_code' => 'KM'],
        ['name' => 'Congo', 'iso3' => 'COG', 'revised_code' => 'CG'],
        ['name' => 'Cook Islands', 'iso3' => 'COK', 'revised_code' => 'CK'],
        ['name' => 'Costa Rica', 'iso3' => 'CRI', 'revised_code' => 'CR'],
        ['name' => 'Cote D\'Ivoire (Ivory Coast)', 'iso3' => 'CIV', 'revised_code' => 'CI'],
        ['name' => 'Croatia', 'iso3' => 'HRV', 'revised_code' => 'HR'],
        ['name' => 'Cuba', 'iso3' => 'CUB', 'revised_code' => 'CU'],
        ['name' => 'Curaçao', 'iso3' => 'CUW', 'revised_code' => 'CW'],
        ['name' => 'Cyprus', 'iso3' => 'CYP', 'revised_code' => 'CY'],
        ['name' => 'Czech Republic', 'iso3' => 'CZE', 'revised_code' => 'CZ'],
        ['name' => 'Democratic Republic of the Congo', 'iso3' => 'COD', 'revised_code' => 'CD'],
        ['name' => 'Denmark', 'iso3' => 'DNK', 'revised_code' => 'DK'],
        ['name' => 'Djibouti', 'iso3' => 'DJI', 'revised_code' => 'DJ'],
        ['name' => 'Dominica', 'iso3' => 'DMA', 'revised_code' => 'DM'],
        ['name' => 'Dominican Republic', 'iso3' => 'DOM', 'revised_code' => 'DO'],
        ['name' => 'East Timor', 'iso3' => 'TLS', 'revised_code' => 'TL'],
        ['name' => 'Ecuador', 'iso3' => 'ECU', 'revised_code' => 'EC'],
        ['name' => 'Egypt', 'iso3' => 'EGY', 'revised_code' => 'EG'],
        ['name' => 'El Salvador', 'iso3' => 'SLV', 'revised_code' => 'SV'],
        ['name' => 'Equatorial Guinea', 'iso3' => 'GNQ', 'revised_code' => 'GQ'],
        ['name' => 'Eritrea', 'iso3' => 'ERI', 'revised_code' => 'ER'],
        ['name' => 'Estonia', 'iso3' => 'EST', 'revised_code' => 'EE'],
        ['name' => 'Ethiopia', 'iso3' => 'ETH', 'revised_code' => 'ET'],
        ['name' => 'Falkland Islands', 'iso3' => 'FLK', 'revised_code' => 'FK'],
        ['name' => 'Faroe Islands', 'iso3' => 'FRO', 'revised_code' => 'FO'],
        ['name' => 'Fiji Islands', 'iso3' => 'FJI', 'revised_code' => 'FJ'],
        ['name' => 'Finland', 'iso3' => 'FIN', 'revised_code' => 'FI'], // 12
        ['name' => 'France', 'iso3' => 'FRA', 'revised_code' => 'FR'], // 13
        ['name' => 'French Guiana', 'iso3' => 'GUF', 'revised_code' => 'GF'],
        ['name' => 'French Polynesia', 'iso3' => 'PYF', 'revised_code' => 'PF'],
        ['name' => 'French Southern Territories', 'iso3' => 'ATF', 'revised_code' => 'TF'],
        ['name' => 'Gabon', 'iso3' => 'GAB', 'revised_code' => 'GA'],
        ['name' => 'Gambia The', 'iso3' => 'GMB', 'revised_code' => 'GM'],
        ['name' => 'Georgia', 'iso3' => 'GEO', 'revised_code' => 'GE'],
        ['name' => 'Germany', 'iso3' => 'DEU', 'revised_code' => 'DE'], // 14
        ['name' => 'Ghana', 'iso3' => 'GHA', 'revised_code' => 'GH'],
        ['name' => 'Gibraltar', 'iso3' => 'GIB', 'revised_code' => 'GI'],
        ['name' => 'Greece', 'iso3' => 'GRC', 'revised_code' => 'GR'],
        ['name' => 'Greenland', 'iso3' => 'GRL', 'revised_code' => 'GL'],
        ['name' => 'Grenada', 'iso3' => 'GRD', 'revised_code' => 'GD'],
        ['name' => 'Guadeloupe', 'iso3' => 'GLP', 'revised_code' => 'GP'],
        ['name' => 'Guam', 'iso3' => 'GUM', 'revised_code' => 'GU'],
        ['name' => 'Guatemala', 'iso3' => 'GTM', 'revised_code' => 'GT'],
        ['name' => 'Guernsey and Alderney', 'iso3' => 'GGY', 'revised_code' => 'GG'],
        ['name' => 'Guinea', 'iso3' => 'GIN', 'revised_code' => 'GN'],
        ['name' => 'Guinea-Bissau', 'iso3' => 'GNB', 'revised_code' => 'GW'],
        ['name' => 'Guyana', 'iso3' => 'GUY', 'revised_code' => 'GY'],
        ['name' => 'Haiti', 'iso3' => 'HTI', 'revised_code' => 'HT'],
        ['name' => 'Heard Island and McDonald Islands', 'iso3' => 'HMD', 'revised_code' => 'HM'],
        ['name' => 'Honduras', 'iso3' => 'HND', 'revised_code' => 'HN'],
        ['name' => 'Hong Kong S.A.R.', 'iso3' => 'HKG', 'revised_code' => 'HK'], // 15
        ['name' => 'Hungary', 'iso3' => 'HUN', 'revised_code' => 'HU'],
        ['name' => 'Iceland', 'iso3' => 'ISL', 'revised_code' => 'IS'],
        ['name' => 'India', 'iso3' => 'IND', 'revised_code' => 'IND'], // 16
        ['name' => 'Indonesia', 'iso3' => 'IDN', 'revised_code' => 'ID'], // 17
        ['name' => 'Iran', 'iso3' => 'IRN', 'revised_code' => 'IR'],
        ['name' => 'Iraq', 'iso3' => 'IRQ', 'revised_code' => 'IQ'],
        ['name' => 'Ireland', 'iso3' => 'IRL', 'revised_code' => 'IE'],
        ['name' => 'Israel', 'iso3' => 'ISR', 'revised_code' => 'IL'], // 18
        ['name' => 'Italy', 'iso3' => 'ITA', 'revised_code' => 'IT'], // 19
        ['name' => 'Jamaica', 'iso3' => 'JAM', 'revised_code' => 'JM'],
        ['name' => 'Japan', 'iso3' => 'JPN', 'revised_code' => 'JP'], // 20
        ['name' => 'Jersey', 'iso3' => 'JEY', 'revised_code' => 'JE'],
        ['name' => 'Jordan', 'iso3' => 'JOR', 'revised_code' => 'JO'],
        ['name' => 'Kazakhstan', 'iso3' => 'KAZ', 'revised_code' => 'KZ'],
        ['name' => 'Kenya', 'iso3' => 'KEN', 'revised_code' => 'KE'], // 22
        ['name' => 'Kiribati', 'iso3' => 'KIR', 'revised_code' => 'KI'],
        ['name' => 'Kosovo', 'iso3' => 'XKX', 'revised_code' => 'XK'],
        ['name' => 'Kuwait', 'iso3' => 'KWT', 'revised_code' => 'KUWA'], // 21
        ['name' => 'Kyrgyzstan', 'iso3' => 'KGZ', 'revised_code' => 'KG'],
        ['name' => 'Laos', 'iso3' => 'LAO', 'revised_code' => 'LA'],
        ['name' => 'Latvia', 'iso3' => 'LVA', 'revised_code' => 'LV'],
        ['name' => 'Lebanon', 'iso3' => 'LBN', 'revised_code' => 'LB'], // 23
        ['name' => 'Lesotho', 'iso3' => 'LSO', 'revised_code' => 'LS'],
        ['name' => 'Liberia', 'iso3' => 'LBR', 'revised_code' => 'LR'],
        ['name' => 'Libya', 'iso3' => 'LBY', 'revised_code' => 'LY'],
        ['name' => 'Liechtenstein', 'iso3' => 'LIE', 'revised_code' => 'LI'],
        ['name' => 'Lithuania', 'iso3' => 'LTU', 'revised_code' => 'LT'],
        ['name' => 'Luxembourg', 'iso3' => 'LUX', 'revised_code' => 'LU'],
        ['name' => 'Macau S.A.R.', 'iso3' => 'MAC', 'revised_code' => 'MO'],
        ['name' => 'Madagascar', 'iso3' => 'MDG', 'revised_code' => 'MG'],
        ['name' => 'Malawi', 'iso3' => 'MWI', 'revised_code' => 'MW'],
        ['name' => 'Malaysia', 'iso3' => 'MYS', 'revised_code' => 'MY'], // 24
        ['name' => 'Maldives', 'iso3' => 'MDV', 'revised_code' => 'MV'],
        ['name' => 'Mali', 'iso3' => 'MLI', 'revised_code' => 'ML'],
        ['name' => 'Malta', 'iso3' => 'MLT', 'revised_code' => 'MT'],
        ['name' => 'Man (Isle of)', 'iso3' => 'IMN', 'revised_code' => 'IM'],
        ['name' => 'Marshall Islands', 'iso3' => 'MHL', 'revised_code' => 'MH'],
        ['name' => 'Martinique', 'iso3' => 'MTQ', 'revised_code' => 'MQ'],
        ['name' => 'Mauritania', 'iso3' => 'MRT', 'revised_code' => 'MR'],
        ['name' => 'Mauritius', 'iso3' => 'MUS', 'revised_code' => 'MU'], // 26
        ['name' => 'Mayotte', 'iso3' => 'MYT', 'revised_code' => 'YT'],
        ['name' => 'Mexico', 'iso3' => 'MEX', 'revised_code' => 'MX'],
        ['name' => 'Micronesia', 'iso3' => 'FSM', 'revised_code' => 'FM'],
        ['name' => 'Moldova', 'iso3' => 'MDA', 'revised_code' => 'MD'],
        ['name' => 'Monaco', 'iso3' => 'MCO', 'revised_code' => 'MC'],
        ['name' => 'Mongolia', 'iso3' => 'MNG', 'revised_code' => 'MN'],
        ['name' => 'Montenegro', 'iso3' => 'MNE', 'revised_code' => 'ME'],
        ['name' => 'Montserrat', 'iso3' => 'MSR', 'revised_code' => 'MS'],
        ['name' => 'Morocco', 'iso3' => 'MAR', 'revised_code' => 'MA'],
        ['name' => 'Mozambique', 'iso3' => 'MOZ', 'revised_code' => 'MZ'],
        ['name' => 'Myanmar', 'iso3' => 'MMR', 'revised_code' => 'MM'],
        ['name' => 'Namibia', 'iso3' => 'NAM', 'revised_code' => 'NA'],
        ['name' => 'Nauru', 'iso3' => 'NRU', 'revised_code' => 'NR'],
        ['name' => 'Nepal', 'iso3' => 'NPL', 'revised_code' => 'NP'], // 29
        ['name' => 'Netherlands', 'iso3' => 'NLD', 'revised_code' => 'NL'],
        ['name' => 'New Caledonia', 'iso3' => 'NCL', 'revised_code' => 'NC'],
        ['name' => 'New Zealand', 'iso3' => 'NZL', 'revised_code' => 'NZ'], // 36
        ['name' => 'Nicaragua', 'iso3' => 'NIC', 'revised_code' => 'NI'],
        ['name' => 'Niger', 'iso3' => 'NER', 'revised_code' => 'NE'],
        ['name' => 'Nigeria', 'iso3' => 'NGA', 'revised_code' => 'NG'], // 31
        ['name' => 'Niue', 'iso3' => 'NIU', 'revised_code' => 'NU'], // 35
        ['name' => 'Norfolk Island', 'iso3' => 'NFK', 'revised_code' => 'NF'], // 30
        ['name' => 'North Korea', 'iso3' => 'PRK', 'revised_code' => 'KP'],
        ['name' => 'North Macedonia', 'iso3' => 'MKD', 'revised_code' => 'MK'],
        ['name' => 'Northern Mariana Islands', 'iso3' => 'MNP', 'revised_code' => 'MP'],
        ['name' => 'Norway', 'iso3' => 'NOR', 'revised_code' => 'NO'], // 32
        ['name' => 'Oman', 'iso3' => 'OMN', 'revised_code' => 'OM'], // 37
        ['name' => 'Pakistan', 'iso3' => 'PAK', 'revised_code' => 'PK'], // 39
        ['name' => 'Palau', 'iso3' => 'PLW', 'revised_code' => 'PW'],
        ['name' => 'Palestinian Territory Occupied', 'iso3' => 'PSE', 'revised_code' => 'PS'],
        ['name' => 'Panama', 'iso3' => 'PAN', 'revised_code' => 'PA'],
        ['name' => 'Papua new Guinea', 'iso3' => 'PNG', 'revised_code' => 'PG'],
        ['name' => 'Paraguay', 'iso3' => 'PRY', 'revised_code' => 'PY'],
        ['name' => 'Peru', 'iso3' => 'PER', 'revised_code' => 'PE'], // 40
        ['name' => 'Philippines', 'iso3' => 'PHL', 'revised_code' => 'PH'], // 41
        ['name' => 'Pitcairn Island', 'iso3' => 'PCN', 'revised_code' => 'PN'],
        ['name' => 'Poland', 'iso3' => 'POL', 'revised_code' => 'PL'], // 42
        ['name' => 'Portugal', 'iso3' => 'PRT', 'revised_code' => 'PT'],
        ['name' => 'Puerto Rico', 'iso3' => 'PRI', 'revised_code' => 'PR'],
        ['name' => 'Qatar', 'iso3' => 'QAT', 'revised_code' => 'QA'], // 44
        ['name' => 'Reunion', 'iso3' => 'REU', 'revised_code' => 'RE'],
        ['name' => 'Romania', 'iso3' => 'ROU', 'revised_code' => 'RO'],
        ['name' => 'Russia', 'iso3' => 'RUS', 'revised_code' => 'RU'], // 45
        ['name' => 'Rwanda', 'iso3' => 'RWA', 'revised_code' => 'RW'],
        ['name' => 'Saint Helena', 'iso3' => 'SHN', 'revised_code' => 'SH'],
        ['name' => 'Saint Kitts And Nevis', 'iso3' => 'KNA', 'revised_code' => 'KN'],
        ['name' => 'Saint Lucia', 'iso3' => 'LCA', 'revised_code' => 'LC'],
        ['name' => 'Saint Pierre and Miquelon', 'iso3' => 'SPM', 'revised_code' => 'PM'],
        ['name' => 'Saint Vincent And The Grenadines', 'iso3' => 'VCT', 'revised_code' => 'VC'],
        ['name' => 'Saint-Barthelemy', 'iso3' => 'BLM', 'revised_code' => 'BL'],
        ['name' => 'Saint-Martin (French part)', 'iso3' => 'MAF', 'revised_code' => 'MF'],
        ['name' => 'Samoa', 'iso3' => 'WSM', 'revised_code' => 'WS'],
        ['name' => 'San Marino', 'iso3' => 'SMR', 'revised_code' => 'SM'],
        ['name' => 'Sao Tome and Principe', 'iso3' => 'STP', 'revised_code' => 'ST'],
        ['name' => 'Saudi Arabia', 'iso3' => 'SAU', 'revised_code' => 'SA'], // 47
        ['name' => 'Senegal', 'iso3' => 'SEN', 'revised_code' => 'SN'],
        ['name' => 'Serbia', 'iso3' => 'SRB', 'revised_code' => 'RS'],
        ['name' => 'Seychelles', 'iso3' => 'SYC', 'revised_code' => 'SC'],
        ['name' => 'Sierra Leone', 'iso3' => 'SLE', 'revised_code' => 'SL'],
        ['name' => 'Singapore', 'iso3' => 'SGP', 'revised_code' => 'SING'], // 49
        ['name' => 'Sint Maarten (Dutch part)', 'iso3' => 'SXM', 'revised_code' => 'SX'],
        ['name' => 'Slovakia', 'iso3' => 'SVK', 'revised_code' => 'SK'],
        ['name' => 'Slovenia', 'iso3' => 'SVN', 'revised_code' => 'SI'],
        ['name' => 'Solomon Islands', 'iso3' => 'SLB', 'revised_code' => 'SB'],
        ['name' => 'Somalia', 'iso3' => 'SOM', 'revised_code' => 'SO'],
        ['name' => 'South Africa', 'iso3' => 'ZAF', 'revised_code' => 'ZA'], // 46
        ['name' => 'South Georgia', 'iso3' => 'SGS', 'revised_code' => 'GS'],
        ['name' => 'South Korea', 'iso3' => 'KOR', 'revised_code' => 'KR'],
        ['name' => 'South Sudan', 'iso3' => 'SSD', 'revised_code' => 'SS'],
        ['name' => 'Spain', 'iso3' => 'ESP', 'revised_code' => 'ES'],
        ['name' => 'Sri Lanka', 'iso3' => 'LKA', 'revised_code' => 'LK'],
        ['name' => 'Sudan', 'iso3' => 'SDN', 'revised_code' => 'SD'],
        ['name' => 'Suriname', 'iso3' => 'SUR', 'revised_code' => 'SR'],
        ['name' => 'Svalbard And Jan Mayen Islands', 'iso3' => 'SJM', 'revised_code' => 'SJ'],
        ['name' => 'Swaziland', 'iso3' => 'SWZ', 'revised_code' => 'SZ'],
        ['name' => 'Sweden', 'iso3' => 'SWE', 'revised_code' => 'SE'],
        ['name' => 'Switzerland', 'iso3' => 'CHE', 'revised_code' => 'CH'],
        ['name' => 'Syria', 'iso3' => 'SYR', 'revised_code' => 'SY'],
        ['name' => 'Taiwan', 'iso3' => 'TWN', 'revised_code' => 'TW'],
        ['name' => 'Tajikistan', 'iso3' => 'TJK', 'revised_code' => 'TJ'],
        ['name' => 'Tanzania', 'iso3' => 'TZA', 'revised_code' => 'TZ'], // 54
        ['name' => 'Thailand', 'iso3' => 'THA', 'revised_code' => 'TH'], // 55
        ['name' => 'The Bahamas', 'iso3' => 'BHS', 'revised_code' => 'BS'],
        ['name' => 'Togo', 'iso3' => 'TGO', 'revised_code' => 'TG'],
        ['name' => 'Tokelau', 'iso3' => 'TKL', 'revised_code' => 'TK'],
        ['name' => 'Tonga', 'iso3' => 'TON', 'revised_code' => 'TO'],
        ['name' => 'Trinidad And Tobago', 'iso3' => 'TTO', 'revised_code' => 'TT'],
        ['name' => 'Tunisia', 'iso3' => 'TUN', 'revised_code' => 'TN'],
        ['name' => 'Turkey', 'iso3' => 'TUR', 'revised_code' => 'TR'],
        ['name' => 'Turkmenistan', 'iso3' => 'TKM', 'revised_code' => 'TM'],
        ['name' => 'Turks And Caicos Islands', 'iso3' => 'TCA', 'revised_code' => 'TC'],
        ['name' => 'Tuvalu', 'iso3' => 'TUV', 'revised_code' => 'TV'],
        ['name' => 'Uganda', 'iso3' => 'UGA', 'revised_code' => 'UG'],
        ['name' => 'Ukraine', 'iso3' => 'UKR', 'revised_code' => 'UA'],
        ['name' => 'United Arab Emirates', 'iso3' => 'ARE', 'revised_code' => 'UAE'], // 56
        ['name' => 'United Kingdom', 'iso3' => 'GBR', 'revised_code' => 'GB'], // 58
        ['name' => 'United States', 'iso3' => 'USA', 'revised_code' => 'USA'], // 59
        ['name' => 'United States Minor Outlying Islands', 'iso3' => 'UMI', 'revised_code' => 'UM'],
        ['name' => 'Uruguay', 'iso3' => 'URY', 'revised_code' => 'UY'],
        ['name' => 'Uzbekistan', 'iso3' => 'UZB', 'revised_code' => 'UZ'],
        ['name' => 'Vanuatu', 'iso3' => 'VUT', 'revised_code' => 'VU'],
        ['name' => 'Vatican City State (Holy See)', 'iso3' => 'VAT', 'revised_code' => 'VA'],
        ['name' => 'Venezuela', 'iso3' => 'VEN', 'revised_code' => 'VE'],
        ['name' => 'Vietnam', 'iso3' => 'VNM', 'revised_code' => 'VN'],
        ['name' => 'Virgin Islands (British)', 'iso3' => 'VGB', 'revised_code' => 'VG'],
        ['name' => 'Virgin Islands (US)', 'iso3' => 'VIR', 'revised_code' => 'VI'],
        ['name' => 'Wallis And Futuna Islands', 'iso3' => 'WLF', 'revised_code' => 'WF'],
        ['name' => 'Western Sahara', 'iso3' => 'ESH', 'revised_code' => 'EH'],
        ['name' => 'Yemen', 'iso3' => 'YEM', 'revised_code' => 'YE'], // 60
        ['name' => 'Zambia', 'iso3' => 'ZMB', 'revised_code' => 'ZM'],
        ['name' => 'Zimbabwe', 'iso3' => 'ZWE', 'revised_code' => 'ZW'],
    ];

    const PURPOSE_OF_REMITTANCES = [
        'build-acquisition-renovation-property' => '10',
        'business-travel' => '13',
        'buying-goods-from-suppliers' => '10',
        'capital-transfer' => '03',
        'charity-donation' => '02',
        'compensation' => '06',
        'educational-expenses' => '12',
        'employee-payroll' => '10',
        'family-maintenance-or-savings' => '04',
        'family-or-living-expense' => '04',
        'goods-trade' => '10',
        'grants-and-gifts' => '02',
        'insurance-premium' => '10',
        'investment-in-equity-shares' => '03',
        'investment-in-real-estate' => '03',
        'investment-in-securities' => '03',
        'medical-expenses' => '11',
        'pay-employee-salary' => '10',
        'payment-for-goods' => '10',
        'payment-for-services' => '10',
        'payment-to-foreign-worker-agency' => '01',
        'personal-asset-allocation' => '03',
        'personal-travels-and-tours' => '13',
        'religious-festival' => '04',
        'rental-payment' => '08',
        'repatriation-of-business-profits' => '03',
        'repayment-of-loans' => '10',
        'return-of-export-trade' => '10',
        'services-trade' => '10',
        'tax-payment' => '10',
        'travel-and-transportation-expenses' => '13',
        'travel-expenses' => '13',
    ];

    public $signature = 'remit:agrani-bank-setup';

    public $description = 'install/update required fields for agrani bank';

    public function handle(): int
    {
        try {
            if (Core::packageExists('MetaData')) {
                $this->updateRemittancePurpose();
                $this->addCountryCodeToCountries();
            } else {
                $this->info('`fintech/metadata` is not installed. Skipped');
            }

            if (Core::packageExists('Business')) {
                $this->addServiceVendor();
            } else {
                $this->info('`fintech/business` is not installed. Skipped');
            }

            $this->info('Agrani Bank Remit service vendor setup completed.');

            return self::SUCCESS;

        } catch (Throwable $th) {

            $this->error($th->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @throws \Exception
     */
    private function updateRemittancePurpose(): void
    {

        $bar = $this->output->createProgressBar(count(self::PURPOSE_OF_REMITTANCES));

        $bar->start();

        foreach (self::PURPOSE_OF_REMITTANCES as $slug => $code) {

            if ($purposeOfRemittance = MetaData::remittancePurpose()->findWhere(['code' => $slug])) {

                $vendor_code = $purposeOfRemittance->vendor_code;

                if ($vendor_code != null) {
                    if (is_string($vendor_code)) {
                        $vendor_code = json_decode($vendor_code, true);
                    }

                    $vendor_code['remit']['agranibank'] = $code;

                    if (! MetaData::remittancePurpose()->update($purposeOfRemittance->getKey(), ['vendor_code' => $vendor_code])) {
                        throw new \Exception("Purpose of Remittance ID: {$purposeOfRemittance->getKey()} update failed.");
                    }
                }

            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->info('Purpose of remittance metadata updated successfully.');
    }

    /**
     * @throws \Exception
     */
    private function addCountryCodeToCountries(): void
    {
        $bar = $this->output->createProgressBar(count(self::COUNTRY_CODES));

        $bar->start();

        foreach (self::COUNTRY_CODES as $countryArray) {

            if ($country = MetaData::country()->findWhere(['iso3' => $countryArray['iso3']])) {

                $vendor_code = $country->vendor_code;

                if ($vendor_code != null) {

                    if (is_string($vendor_code)) {
                        $vendor_code = json_decode($vendor_code, true);
                    }

                    $vendor_code['remit']['agranibank'] = $countryArray['revised_code'];

                    if (! MetaData::remittancePurpose()->update($country->getKey(), ['vendor_code' => $vendor_code])) {
                        throw new \Exception("Country ID: {$country->getKey()} update failed.");
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();

        $this->line('');
        $this->info('Country codes metadata updated successfully.');
    }

    private function addServiceVendor(): void
    {
        $dir = __DIR__.'/../../resources/img/service_vendor/';

        $vendor = [
            'service_vendor_name' => 'Agrani Bank',
            'service_vendor_slug' => 'agranibank',
            'service_vendor_data' => [],
            'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents("{$dir}/logo_png/agrani.png")),
            'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents("{$dir}/logo_svg/agrani.svg")),
            'enabled' => false,
        ];

        if (Business::serviceVendor()->findWhere(['service_vendor_slug' => $vendor['service_vendor_slug']])) {
            $this->info('Service vendor already exists. Skipping');
        } else {
            Business::serviceVendor()->create($vendor);
            $this->info('Service vendor created successfully.');
        }
    }

    // add country code all country
    //    public function addCountryCodeToCountries(): void
    //    {
    //        if (Core::packageExists('MetaData')) {
    //            MetaData::country()
    //                ->list(['paginate' => false])
    //                ->each(function ($country) {
    //                    $countryData = $country->country_data;
    //                    $countryData['vendor_code']['agrani_code'] = self::COUNTRY_CODES[$country->iso3]['agrani_code'] ?? null;
    //                    MetaData::country()->update($country->getKey(), ['country_data' => $countryData]);
    //                    $this->info("Country ID: {$country->getKey()} successful.");
    //                });
    //        }
    //    }
}
