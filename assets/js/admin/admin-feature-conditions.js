function diviOverlaysFeatureConditions(ETBuilderBackend,conditionsi18n){let woocommmerceConditions={};(window.woocommerce_params)||(woocommmerceConditions={productPurchase:conditionsi18n["Product Purchase"],cartContents:conditionsi18n["Cart Contents"],productStock:conditionsi18n["Product Stock"]});function C(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function E(e){for(var t=1;t<arguments.length;t+=1){var n=null!=arguments[t]?arguments[t]:{};t%2?C(Object(n),!0).forEach((function(t){M(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):C(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function M(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}this.defaultFields={displayRule:{label:conditionsi18n["Display Only If"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},dynamicPosts:{type:"multiselect",default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:''},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.dataConditionNames={postInfo:{postType:conditionsi18n["Post Type"],categories:conditionsi18n["Post Category"],tags:conditionsi18n["Post Tag"],author:conditionsi18n["Author"]},location:E({tagPage:conditionsi18n["Tag Page"],categoryPage:conditionsi18n["Category Page"],dateArchive:conditionsi18n["Date Archive"],searchResults:conditionsi18n["Search Results"],attachment:"Media"},ETBuilderBackend.registeredPostTypeOptions),user:{loggedInStatus:conditionsi18n["Logged In Status"],userRole:conditionsi18n["User Role"]},interaction:E(E({dateTime:conditionsi18n["Date & Time"].replace(/&amp;/g,"&"),pageVisit:conditionsi18n["Page Visit"],postVisit:conditionsi18n["Post Visit"]},woocommmerceConditions),{},{numberOfViews:conditionsi18n["Number of Views"],urlParameter:conditionsi18n["URL Parameter"]}),device:{browser:conditionsi18n["Browser"],operatingSystem:conditionsi18n["Operating System"],cookie:conditionsi18n["Cookie"]}};this.loggedInStatusField={displayRule:{label:conditionsi18n["Display Only If"],type:"select",options:{loggedIn:conditionsi18n["User is Logged In"],loggedOut:conditionsi18n["User is Logged Out"]},default:"loggedIn"},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Logged In Status"],defaultIfEmpty:!0},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.userRoleFields={displayRule:{label:conditionsi18n["Display Only If User Role"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},userRoles:{type:"multiselect",default:[]},userIds:{label:"",type:"text",default:"User IDs separated by commas"},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["User Role"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.postTypeFields={displayRule:{label:conditionsi18n["Display Only If Post Type"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},postTypes:{type:"multiselect",default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Post Type"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.authorFields={displayRule:{label:conditionsi18n["Display Only If Author"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},authors:{type:"multiselect",default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Author"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.categoriesFields={displayRule:{label:conditionsi18n["Display Only If Post Category"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},categories:{type:"multiselect_categories",default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Post Category"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.tagsFields={displayRule:{label:conditionsi18n["Display Only If Post Tag"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},tags:{type:"multiselect_tags",default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Post Tag"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.dateArchiveFields=function(){let i=new Date,l=i.getFullYear()+"-"+(i.getMonth()+1)+"-"+i.getDate(),u={dateArchiveDisplay:{label:conditionsi18n["Display Only on Date Archives"],type:"select",options:{isAfter:conditionsi18n["Is After"],isBefore:conditionsi18n["Is Before"]},default:"isAfter"},dateArchive:{type:"date_picker",showTimeSelect:!1,default:l},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Date Archive"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};return u};this.productPurchaseFields={displayRule:{label:conditionsi18n["Display Only If User"],type:"select",options:{hasBoughtProduct:conditionsi18n["Has Bought a Product"],hasNotBoughtProduct:conditionsi18n["Has Not Bought a Product"],hasBoughtSpecificProduct:[conditionsi18n["Has Bought a Specific Product"],'products'],hasNotBoughtSpecificProduct:[conditionsi18n["Has Not Bought a Specific Product"],'products']},default:"hasBoughtProduct",showhidefields:true},products:{type:"multiselect",default:[],visibility:!1},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Product Purchase"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.cartContentsField={displayRule:{label:conditionsi18n["Display Only If User's Cart"].replace(/&#039;/g,"'"),type:"select",options:{hasProducts:conditionsi18n["Has Products"],isEmpty:conditionsi18n["Is Empty"],hasSpecificProduct:[conditionsi18n["Has a Specific Product"],'products'],doesNotHaveSpecificProduct:[conditionsi18n["Does Not Have a Specific Product"],'products']},default:"hasProducts",showhidefields:true},products:{type:"multiselect",default:[],visibility:!1},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Cart Contents"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.productStockFields={displayRule:{label:conditionsi18n["Display Only If a Specific Product"],type:"select",options:{isInStock:conditionsi18n["Is in stock"],isOutOfStock:conditionsi18n["Is out of stock"]},default:"isInStock"},products:{type:"multiselect",default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Product Stock"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.searchResultsFields={displayRule:{label:conditionsi18n["Display Only on Search Results for"],type:"select",options:{specificSearchQueries:conditionsi18n["Specific Search Queries"],excludedSearchQueries:conditionsi18n["Excluded Search Queries"]},default:"specificSearchQueries"},specificSearchQueries:{label:conditionsi18n["Specific Search Queries"],type:"text",default:conditionsi18n["Search queries separated by commas"]},excludedSearchQueries:{label:conditionsi18n["Excluded Search Queries"],type:"text",default:conditionsi18n["Search queries separated by commas"],visibility:!1},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Search Results"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.operatingSystemFields={displayRule:{label:conditionsi18n["Display Only If Operating System"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},operatingSystems:{type:"checkboxes",options:{windows:{value:"windows",label:"Windows"},macos:{value:"macos",label:"Mac OS"},linux:{value:"linux",label:"Linux"},android:{value:"android",label:"Android"},iphone:{value:"iphone",label:"iPhone (iOS)"},ipad:{value:"ipad",label:"iPad (iOS/iPadOS)"},ipod:{value:"ipod",label:"iPod (iOS)"},appletv:{value:"appletv",label:"Apple TV (tvOS)"},playstation:{value:"playstation",label:"Playstation"},xbox:{value:"xbox",label:"Xbox"},nintendo:{value:"nintendo",label:"Nintendo"},kindle:{value:"webos",label:"Web OS"}},default:""},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Operating System"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.browserFields={displayRule:{label:conditionsi18n["Display Only If Browser"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},browsers:{type:"checkboxes",options:{chrome:{value:"chrome",label:conditionsi18n["Chromium Browsers (Chrome, Edge, etc)"]},firefox:{value:"firefox",label:"Firefox"},safari:{value:"safari",label:"Safari"},edge:{value:"edge",label:"Edge"},ie:{value:"ie",label:"Internet Explorer"},opera:{value:"opera",label:"Opera"},maxthon:{value:"maxthon",label:"Maxthon"},ucbrowser:{value:"ucbrowser",label:"UC Browser"}},default:""},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Browser"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.pageVisitFields={displayRule:{label:conditionsi18n["Display Only If User"],type:"select",options:{hasVisitedSpecificPage:conditionsi18n["Has Visited a Specific Page"],hasNotVisitedSpecificPage:conditionsi18n["Has Not Visited a Specific Page"]},default:"hasVisitedSpecificPage"},pages:{type:"multiselect",options:{postType:"page"},default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Page Visit"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.postVisitFields={displayRule:{label:conditionsi18n["Display Only If User"],type:"select",options:{hasVisitedSpecificPage:conditionsi18n["Has Visited a Specific Post"],hasNotVisitedSpecificPage:conditionsi18n["Has Not Visited a Specific Post"]},default:"hasVisitedSpecificPage"},pages:{type:"multiselect",options:{postType:"post"},default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Post Visit"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.cookieFields={displayRule:{label:conditionsi18n["Display Only If"],type:"select",options:{cookieExists:conditionsi18n["Cookie Exists"],cookieDoesNotExist:conditionsi18n["Cookie Does Not Exist"],cookieValueEquals:[conditionsi18n["Cookie Value Equals"],'cookieValue'],cookieValueDoesNotEqual:[conditionsi18n["Cookie Value Does Not Equal"],'cookieValue']},default:"cookieExists",showhidefields:true},cookieName:{type:"text",default:conditionsi18n["Cookie Name"]},cookieValue:{label:"",type:"text",default:conditionsi18n["Cookie Value"],visibility:!1},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Cookie"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.categoryPageFields={displayRule:{label:conditionsi18n["Display Only If Category Page"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},categories:{type:"multiselect_categories",default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Category Page"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.tagPageFields={displayRule:{label:conditionsi18n["Display Only If Tag Page"],type:"select",options:{is:conditionsi18n["Is"],isNot:conditionsi18n["Is Not"]},default:"is"},tags:{type:"multiselect_tags",default:[]},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Tag Page"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};this.numberOfViewsFields={numberOfViews:{label:conditionsi18n["Only Display This Many Times"],type:"text",default:conditionsi18n["Number of Views"],visible:!1,value_type:"int"},resetAfterDuration:{label:conditionsi18n["Reset After Duration"],type:"yes_no_button",options:{on:[conditionsi18n["Yes"],['displayAgainAfter','displayAgainAfterUnit']],off:conditionsi18n["No"]},default:"off",showhidefields:true},displayAgainAfter:{label:conditionsi18n["Display again after"],type:"text",default:"",visibility:!1,value_type:"int"},displayAgainAfterUnit:{type:"select",options:{days:conditionsi18n["Days"],hours:conditionsi18n["Hours"],minutes:conditionsi18n["Minutes"]},default:"days",visibility:!1},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Number of Views"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};var repeatEndOptions={never:conditionsi18n["Never"],untilDate:[conditionsi18n["Until Date"],'repeatUntilDate'],afterNumberOfTimes:[conditionsi18n["After Number of Times"],'repeatTimes']},onSpecificDaysRepeatEndOptions={never:conditionsi18n["Never"],untilDate:[conditionsi18n["Until Date"],'repeatUntilDate']};this.conditionsDateTime={isOnSpecificDate:repeatEndOptions,isOnSpecificDays:onSpecificDaysRepeatEndOptions};this.dateTimeFields=function(){let i=new Date,l="".concat(i.getFullYear(),"-").concat(i.getMonth()+1,"-").concat(i.getDate());let c={displayRule:{label:conditionsi18n["Display Only If Current Date"],type:"select",options:{isAfter:[conditionsi18n["Is After"],['date','time']],isBefore:[conditionsi18n["Is Before"],['date','time']],isOnSpecificDate:[conditionsi18n["Is On a Specific Date"],['date','allDay','repeat','repeatFrequency']],isNotOnSpecificDate:[conditionsi18n["Is Not a Specific Date"],['date','allDay']],isOnSpecificDays:[conditionsi18n["Is On Specific Day(s) of the Week"],['weekdays','allDay','repeat','repeatFrequencySpecificDays']],isFirstDayOfMonth:[conditionsi18n["Is the First Day of the Month"],'allDay'],isLastDayOfMonth:[conditionsi18n["Is the Last Day of the Month"],'allDay']},default:"isAfter",showhidefields:true},date:{type:"date_picker",showTimeSelect:!1,default:l,visibility:!1},time:{hourLabel:conditionsi18n["Hour"],minuteLabel:conditionsi18n["Minute"],type:"input_time",default:"00:00",visibility:!1},weekdays:{type:"checkboxes",visibility:!1,options:{monday:{label:conditionsi18n["Monday"],value:"monday"},tuesday:{label:conditionsi18n["Tuesday"],value:"tuesday"},wednesday:{label:conditionsi18n["Wednesday"],value:"wednesday"},thursday:{label:conditionsi18n["Thursday"],value:"thursday"},friday:{label:conditionsi18n["Friday"],value:"friday"},saturday:{label:conditionsi18n["Saturday"],value:"saturday"},sunday:{label:conditionsi18n["Sunday"],value:"sunday"}},default:"|"},allDay:{label:conditionsi18n["All Day"],type:"yes_no_button",options:{on:"Yes",off:[conditionsi18n["No"],['fromTime','untilTime']]},default:"on",visibility:!1,showhidefields:true},fromTime:{hourLabel:conditionsi18n["From Hour"],minuteLabel:conditionsi18n["From Minute"],type:"input_time",default:"00:00",visibility:!1},untilTime:{hourLabel:conditionsi18n["Until Hour"],minuteLabel:conditionsi18n["Until Minute"],type:"input_time",default:"00:00",visibility:!1},repeat:{label:conditionsi18n["Repeat"],type:"yes_no_button",options:{on:[conditionsi18n["Yes"],['repeatFrequency','repeatFrequencySpecificDays','repeatEnd']],off:"No"},default:"off",visibility:!1,showhidefields:true},repeatFrequency:{label:conditionsi18n["Repeat Frequency"],type:"select",options:{monthly:conditionsi18n["Monthly"],annually:conditionsi18n["Annually"]},default:"monthly",visibility:!1},repeatFrequencySpecificDays:{label:conditionsi18n["Repeat Frequency"],type:"select",options:{weekly:conditionsi18n["Weekly"],firstInstanceOfMonth:conditionsi18n["First Instance of Every Month"],lastInstanceOfMonth:conditionsi18n["Last Instance of Every Month"],everyOther:conditionsi18n["Every Other"]},default:"weekly",visibility:!1},repeatEnd:{label:conditionsi18n["Repeat End"],type:"select",options:repeatEndOptions,default:"never",visibility:!1,showhidefields:true},repeatUntilDate:{type:"date_picker",showTimeSelect:!1,default:l,visibility:!1},repeatTimes:{label:conditionsi18n["Repeat Times"],type:"range",default:"3",unitless:!0,range_settings:{min:0,max:10,step:1},visibility:!1},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["Date & Time"].replace(/&amp;/g,"&")},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}};return c};this.urlParameterFields={selectUrlParameter:{label:conditionsi18n["Display Only If"],type:"select",options:{specificUrlParameter:[conditionsi18n["A Specific URL Parameter"],'urlParameterName'],anyUrlParameter:conditionsi18n["Any URL Parameter"]},default:"specificUrlParameter",showhidefields:true},urlParameterName:{type:"text",default:conditionsi18n["URL Parameter Name"],visibility:!1},displayRule:{type:"select",options:{equals:[conditionsi18n["Equals"],'urlParameterValue'],exist:conditionsi18n["Exist"],doesNotExist:conditionsi18n["Does not Exist"],doesNotEqual:[conditionsi18n["Does not Equal"],'urlParameterValue'],contains:[conditionsi18n["Contains"],'urlParameterValue'],doesNotContain:[conditionsi18n["Does not Contain"],'urlParameterValue']},default:"equals",showhidefields:true},urlParameterValue:{type:"text",default:conditionsi18n["URL Parameter Value"],visibility:!1},adminLabel:{label:conditionsi18n["Admin Label"],type:"text",default:conditionsi18n["URL Parameter"]},enableCondition:{label:conditionsi18n["Enable Condition"],type:"yes_no_button",options:{on:conditionsi18n["Yes"],off:conditionsi18n["No"]},default:"on"}}}