{"version":3,"file":"script.min.js","sources":["script.js"],"names":["__logOnDateChange","sel","bShowFrom","bShowTo","bShowHellip","bShowDays","value","BX","style","display","__logOnReload","log_counter","arMenuItems","findChildren","className","hasClass","i","length","addClass","removeClass","menuButtonText","findChild","innerHTML","message","counter_cont","parseInt","BitrixLFFilter","this","filterPopup","currentName","obInputName","obSearchInput","obInputContainerName","obContainerInput","prototype","initFilter","params","document","forms","flt_date_datesel","onclick","calendar","node","field","bTime","initDestination","name","inputName","inputContainerName","items","department","extranetRoot","key","hasOwnProperty","departmentRelation","SocNetLogDestination","buildDepartmentRelation","init","pathToAjax","searchInput","extranetUser","departmentSelectDisable","bindMainPopup","bindNode","offsetTop","offsetLeft","bindSearchPopup","callback","select","proxy","onSelectDestination","containerInput","resultFieldName","unSelect","onUnSelectDestination","itemsLast","itemsSelected","itemsSelectedUndeleted","isCrmFeed","useClientDatabase","destSort","allowAddUser","allowSearchEmailUsers","userNameTemplate","bind","e","oLFFilter","openDialog","PreventDefault","delegate","BXfpSearch","formName","BXfpSearchBefore","clearInput","arItems","deleteItem","attributes","item","type","search","bUndeleted","BXfpSelectCallback","valueInput","varName","closeDialog","closeSearch","elements","attribute","data-id","id","j","remove","ShowFilterPopup","bindElement","ajax","get","data","closeWait","PopupWindow","closeIcon","autoHide","zIndex","events","onPopupClose","onPopupShow","filter_block","create","html","util","trim","setContent","firstChild","show","__SLFShowExpertModePopup","bindObj","modalWindow","closeByEsc","overlay","buttons","content","children","props","text","attrs","src","width","height","click","method","dataType","url","sessid","bitrix_sessid","closePopup","onsuccess","response","SUCCESS","close","top","location","href","window"],"mappings":"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"}