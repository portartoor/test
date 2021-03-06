;(function(window){
	if (!!window.BX.OpenLinesConfigEdit)
		return;

	var destination = function(params, type) {
		this.p = (!!params ? params : {});
		if (!!params["SELECTED"])
		{
			var res = {}, tp, j;
			for (tp in params["SELECTED"])
			{
				if (params["SELECTED"].hasOwnProperty(tp) && typeof params["SELECTED"][tp] == "object")
				{
					for (j in params["SELECTED"][tp])
					{
						if (params["SELECTED"][tp].hasOwnProperty(j))
						{
							if (tp == 'USERS')
								res['U' + params["SELECTED"][tp][j]] = 'users';
							else if (tp == 'SG')
								res['SG' + params["SELECTED"][tp][j]] = 'sonetgroups';
							else if (tp == 'DR')
								res['DR' + params["SELECTED"][tp][j]] = 'department';
						}
					}
				}
			}
			this.p["SELECTED"] = res;
		}

		this.nodes = {};
		var makeDepartmentTree = function(id, relation)
		{
			var arRelations = {}, relId, arItems, x;
			if (relation[id])
			{
				for (x in relation[id])
				{
					if (relation[id].hasOwnProperty(x))
					{
						relId = relation[id][x];
						arItems = [];
						if (relation[relId] && relation[relId].length > 0)
							arItems = makeDepartmentTree(relId, relation);
						arRelations[relId] = {
							id: relId,
							type: 'category',
							items: arItems
						};
					}
				}
			}
			return arRelations;
		},
		buildDepartmentRelation = function(department)
		{
			var relation = {}, p;
			for(var iid in department)
			{
				if (department.hasOwnProperty(iid))
				{
					p = department[iid]['parent'];
					if (!relation[p])
						relation[p] = [];
					relation[p][relation[p].length] = iid;
				}
			}
			return makeDepartmentTree('DR0', relation);
		};
		if (true || type == 'users')
		{
			this.params = {
				'name' : null,
				'searchInput' : null,
				'extranetUser' :  (this.p['EXTRANET_USER'] == "Y"),
				'bindMainPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				'bindSearchPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				departmentSelectDisable : true,
				'callback' : {
					'select' : BX.delegate(this.select, this),
					'unSelect' : BX.delegate(this.unSelect, this),
					'openDialog' : BX.delegate(this.openDialog, this),
					'closeDialog' : BX.delegate(this.closeDialog, this),
					'openSearch' : BX.delegate(this.openDialog, this),
					'closeSearch' : BX.delegate(this.closeSearch, this)
				},
				items : {
					users : (!!this.p['USERS'] ? this.p['USERS'] : {}),
					groups : {},
					sonetgroups : {},
					department : (!!this.p['DEPARTMENT'] ? this.p['DEPARTMENT'] : {}),
					departmentRelation : (!!this.p['DEPARTMENT'] ? buildDepartmentRelation(this.p['DEPARTMENT']) : {}),
					contacts : {},
					companies : {},
					leads : {},
					deals : {}
				},
				itemsLast : {
					users : (!!this.p['LAST'] && !!this.p['LAST']['USERS'] ? this.p['LAST']['USERS'] : {}),
					sonetgroups : {},
					department : {},
					groups : {},
					contacts : {},
					companies : {},
					leads : {},
					deals : {},
					crm : []
				},
				itemsSelected : (!!this.p['SELECTED'] ? BX.clone(this.p['SELECTED']) : {}),
				isCrmFeed : false,
				destSort : (!!this.p['DEST_SORT'] ? BX.clone(this.p['DEST_SORT']) : {})
			}
		}
	}, destinationInstance = null;
	destination.prototype = {
		setInput : function(node, inputName)
		{
			node = BX(node);
			if (!!node && !node.hasAttribute("bx-destination-id"))
			{
				var id = 'destination' + ('' + new Date().getTime()).substr(6), res;
				node.setAttribute('bx-destination-id', id);
				res = new destInput(id, node, inputName);
				this.nodes[id] = node;
				BX.defer_proxy(function(){
					this.params.name = res.id;
					this.params.searchInput = res.nodes.input;
					this.params.bindMainPopup.node = res.nodes.container;
					this.params.bindSearchPopup.node = res.nodes.container;

					BX.SocNetLogDestination.init(this.params);
				}, this)();
			}
		},
		select : function(item, type, search, bUndeleted, id)
		{
			var type1 = type, prefix = 'S';

			if (type == 'groups')
			{
				type1 = 'all-users';
			}
			else if (BX.util.in_array(type, ['contacts', 'companies', 'leads', 'deals']))
			{
				type1 = 'crm';
			}

			if (type == 'sonetgroups')
			{
				prefix = 'SG';
			}
			else if (type == 'groups')
			{
				prefix = 'UA';
			}
			else if (type == 'users')
			{
				prefix = 'U';
			}
			else if (type == 'department')
			{
				prefix = 'DR';
			}
			else if (type == 'contacts')
			{
				prefix = 'CRMCONTACT';
			}
			else if (type == 'companies')
			{
				prefix = 'CRMCOMPANY';
			}
			else if (type == 'leads')
			{
				prefix = 'CRMLEAD';
			}
			else if (type == 'deals')
			{
				prefix = 'CRMDEAL';
			}

			var stl = (bUndeleted ? ' bx-destination-undelete' : '');
			stl += (type == 'sonetgroups' && typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(item.entityId, window['arExtranetGroupID']) ? ' bx-destination-extranet' : '');

			var el = BX.create("span", {
				attrs : {
					'data-id' : item.id
				},
				props : {
					className : "bx-destination bx-destination-"+type1+stl
				},
				children: [
					BX.create("span", {
						props : {
							'className' : "bx-destination-text"
						},
						html : item.name
					})
				]
			});

			if(!bUndeleted)
			{
				el.appendChild(BX.create("span", {
					props : {
						'className' : "bx-destination-del-but"
					},
					events : {
						'click' : function(e){
							BX.SocNetLogDestination.deleteItem(item.id, type, id);
							BX.PreventDefault(e)
						},
						'mouseover' : function(){
							BX.addClass(this.parentNode, 'bx-destination-hover');
						},
						'mouseout' : function(){
							BX.removeClass(this.parentNode, 'bx-destination-hover');
						}
					}
				}));
			}
			BX.onCustomEvent(this.nodes[id], 'select', [item, el, prefix]);
		},
		unSelect : function(item, type, search, id)
		{
			BX.onCustomEvent(this.nodes[id], 'unSelect', [item]);
		},
		openDialog : function(id)
		{
			BX.onCustomEvent(this.nodes[id], 'openDialog', []);
		},
		closeDialog : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeDialog', []);
				this.disableBackspace();
			}
		},
		closeSearch : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeSearch', []);
				this.disableBackspace();
			}
		},
		disableBackspace : function()
		{
			if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable !== null)
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

			BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event){
				if (event.keyCode == 8)
				{
					BX.PreventDefault(event);
					return false;
				}
				return true;
			});
			setTimeout(function(){
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
				BX.SocNetLogDestination.backspaceDisable = null;
			}, 5000);
		}
	};
	var destInput = function(id, node, inputName)
	{
		this.node = node;
		this.id = id;
		this.inputName = inputName;
		this.node.appendChild(BX.create('SPAN', {
			props : { className : "bx-destination-wrap" },
			html : [
				'<span id="', this.id, '-container"><span class="bx-destination-wrap-item"></span></span>',
				'<span class="bx-destination-input-box" id="', this.id, '-input-box">',
					'<input type="text" value="" class="bx-destination-input" id="', this.id, '-input">',
				'</span>',
				'<a href="#" class="bx-destination-add" id="', this.id, '-add-button"></a>'
			].join('')}));
		BX.defer_proxy(this.bind, this)();
	};
	destInput.prototype = {
		bind : function()
		{
			this.nodes = {
				inputBox : BX(this.id + '-input-box'),
				input : BX(this.id + '-input'),
				container : BX(this.id + '-container'),
				button : BX(this.id + '-add-button')
			};
			BX.bind(this.nodes.input, 'keyup', BX.proxy(this.search, this));
			BX.bind(this.nodes.input, 'keydown', BX.proxy(this.searchBefore, this));
			BX.bind(this.nodes.button, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id); BX.PreventDefault(e); }, this));
			BX.bind(this.nodes.container, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id); BX.PreventDefault(e); }, this));
			this.onChangeDestination();
			BX.addCustomEvent(this.node, 'select', BX.proxy(this.select, this));
			BX.addCustomEvent(this.node, 'unSelect', BX.proxy(this.unSelect, this));
			BX.addCustomEvent(this.node, 'delete', BX.proxy(this.delete, this));
			BX.addCustomEvent(this.node, 'openDialog', BX.proxy(this.openDialog, this));
			BX.addCustomEvent(this.node, 'closeDialog', BX.proxy(this.closeDialog, this));
			BX.addCustomEvent(this.node, 'closeSearch', BX.proxy(this.closeSearch, this));
		},
		select : function(item, el, prefix)
		{
			if (BX.message('LM_BUSINESS_USERS_ON') == 'Y' && BX.message('LM_BUSINESS_USERS').split(',').indexOf(item.id) == -1)
			{
				BX.SocNetLogDestination.closeDialog(this.id);
				imolOpenTrialPopup('imol_queue');
				return false;
			}
			if(!BX.findChild(this.nodes.container, { attr : { 'data-id' : item.id }}, false, false))
			{
				el.appendChild(BX.create("INPUT", { props : {
						type : "hidden",
						name : ('CONFIG['+this.inputName+']'+ '[' + prefix + '][]'),
						value : item.id
					}
				}));
				this.nodes.container.appendChild(el);
			}
			this.onChangeDestination();
		},
		unSelect : function(item)
		{
			var elements = BX.findChildren(this.nodes.container, {attribute: {'data-id': ''+item.id+''}}, true);
			if (elements !== null)
			{
				for (var j = 0; j < elements.length; j++)
					BX.remove(elements[j]);
			}
			this.onChangeDestination();
		},
		onChangeDestination : function()
		{
			this.nodes.input.innerHTML = '';
			this.nodes.button.innerHTML = (BX.SocNetLogDestination.getSelectedCount(this.id) <= 0 ? BX.message("LM_ADD1") : BX.message("LM_ADD2"));
		},
		openDialog : function()
		{
			BX.style(this.nodes.inputBox, 'display', 'inline-block');
			BX.style(this.nodes.button, 'display', 'none');
			BX.focus(this.nodes.input);
		},
		closeDialog : function()
		{
			if (this.nodes.input.value.length <= 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}
		},
		closeSearch : function()
		{
			if (this.nodes.input.value.length > 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}
		},
		searchBefore : function(event)
		{
			if (event.keyCode == 8 && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.sendEvent = false;
				BX.SocNetLogDestination.deleteLastItem(this.id);
			}
			return true;
		},
		search : function(event)
		{
			if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
				return false;

			if (event.keyCode == 13)
			{
				BX.SocNetLogDestination.selectFirstSearchItem(this.id);
				return true;
			}
			if (event.keyCode == 27)
			{
				this.nodes.input.value = '';
				BX.style(this.nodes.button, 'display', 'inline');
			}
			else
			{
				BX.SocNetLogDestination.search(this.nodes.input.value, true, this.id);
			}

			if (!BX.SocNetLogDestination.isOpenDialog() && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.openDialog(this.id);
			}
			else if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
			{
				BX.SocNetLogDestination.closeDialog();
			}
			if (event.keyCode == 8)
			{
				BX.SocNetLogDestination.sendEvent = true;
			}
			return true;
		}
	};

	window.BX.OpenLinesConfigEdit = {
		popupTooltip: {},
		initDestination : function(node, inputName, params)
		{
			if (destinationInstance === null)
				destinationInstance = new destination(params);
			destinationInstance.setInput(BX(node), inputName);
		},
		addEventForTooltip : function()
		{
			var arNodes = BX.findChildrenByClassName(BX('tel-set-main-wrap'), "tel-context-help");
			for (var i = 0; i < arNodes.length; i++)
			{
				if (arNodes[i].getAttribute('context-help') == 'y')
					continue;

				arNodes[i].setAttribute('data-id', i);
				arNodes[i].setAttribute('context-help', 'y');
				BX.bind(arNodes[i], 'mouseover', function(){
					var id = this.getAttribute('data-id');
					var text = this.getAttribute('data-text');

					BX.OpenLinesConfigEdit.showTooltip(id, this, text);
				});
				BX.bind(arNodes[i], 'mouseout', function(){
					var id = this.getAttribute('data-id');
					BX.OpenLinesConfigEdit.hideTooltip(id);
				});
			}
		},
		showTooltip : function(id, bind, text)
		{
			if (this.popupTooltip[id])
				this.popupTooltip[id].close();

			this.popupTooltip[id] = new BX.PopupWindow('bx-imopenlines-tooltip', bind, {
				lightShadow: true,
				autoHide: false,
				darkMode: true,
				offsetLeft: 0,
				offsetTop: 2,
				bindOptions: {position: "top"},
				zIndex: 200,
				events : {
					onPopupClose : function() {this.destroy()}
				},
				content : BX.create("div", { attrs : { style : "padding-right: 5px; width: 250px;" }, html: text})
			});
			this.popupTooltip[id].setAngle({offset:13, position: 'bottom'});
			this.popupTooltip[id].show();

			return true;
		},
		hideTooltip : function(id)
		{
			this.popupTooltip[id].close();
			this.popupTooltip[id] = null;
		},
		toggleSelectFormText: function(selector, form, text, textarea)
		{
			if (selector.options[selector.selectedIndex].value == 'form')
			{
				form.style.height = '47px';
				text.style.height = '29px';
				textarea.style.height = '100px';
			}
			else if (selector.options[selector.selectedIndex].value == 'text')
			{
				form.style.height = '0';
				text.style.height = '0';
				textarea.style.height = '100px';
			}
			else
			{
				form.style.height = '0';
				text.style.height = '0';
				textarea.style.height = '0';
			}
		},
		toggleSelectFormOrText: function(selector, form, textarea)
		{
			if (selector.options[selector.selectedIndex].value == 'form')
			{
				form.style.height = '47px';
				textarea.style.height = '0';
			}
			else if (selector.options[selector.selectedIndex].value == 'text' || selector.options[selector.selectedIndex].value == 'quality')
			{
				form.style.height = '0';
				textarea.style.height = '100px';
			}
			else
			{
				form.style.height = '0';
				textarea.style.height = '0';
			}
		},
		toggleCheckboxText: function(checkbox, textarea)
		{
			if (checkbox.checked)
			{
				textarea.style.height = '100px';
			}
			else
			{
				textarea.style.height = '0';
			}
		},
		toggleCheckboxVote: function(checkbox, textarea)
		{
			if (checkbox.checked)
			{
				textarea.style.height = '745px';
			}
			else
			{
				textarea.style.height = '0';
			}
		}

	};
	BX.ready(function(){
		BX.OpenLinesConfigEdit.addEventForTooltip();
	});
})(window);