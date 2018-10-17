import qwest from "qwest";

window.grids = {}; //For debugging purposes
/**
 * @extends {Set}
 */
class GridLocalStorage {
	id;

	/**
	 * @type {Set}
	 */
	data;

	constructor(id) {
		this.id = this.constructor.name + id;
		let data = window.localStorage.getItem(this.id);
		this.data = new Set(data ? JSON.parse(data) : []);
		window.addEventListener("beforeunload", this.persist.bind(this), true);
	}

	persist() {
		window.localStorage.setItem(this.id, JSON.stringify(this.values()));
		return this;
	}

	set(key, on) {
		return on ? this.add(key) : this.remove(key);
	}

	add(key) {
		return this.data.add(Number(key) || key);
	}

	remove(key) {
		return this.data.delete(Number(key) || key);
	}

	size() {
		return this.data.size;
	}

	clear() {
		return this.data.clear();
	}

	has(key) {
		return this.data.has(Number(key) || key);
	}

	values() {
		return Array.from(this.data.values());
	}
}

export default class EntityGrid {

	static GRID_SELECTOR = '[data-entity-grid]';
	static CHECKBOX_SELECTOR = '[data-row-select]';
	static CHECKBOX_ALL_SELECTOR = '[data-row-select=all]';
	static CHECKBOX_ROW_SELECTOR = '[data-row-select]:not([data-row-select=all])';

	/**
	 * @type {GridLocalStorage|Set}
	 */
	ids;

	/**
	 * @type {HTMLDivElement}
	 */
	element;

	/**
	 * @type {string}
	 */
	control;

	/**
	 * @param {HTMLDivElement} element
	 */
	constructor(element) {
		window.grids[element.id] = this;
		if (!element.id) {
			throw Error('EntityGrid element must have id.');
		}
		element.dataGrid = this;
		this.element = element;
		this.control = element.dataset.control;
		this.ids = new GridLocalStorage(element.id);
		element.addEventListener('change', this.onChange.bind(this));
		element.addEventListener('destroy', this.destroy.bind(this));
		const observer = new MutationObserver(this.observe.bind(this));
		observer.observe(element, {childList: true, subtree: true});
		this.init();
	}

	selecting = null;

	onMouseDown(checkbox, e) {
		this.selecting = !checkbox.checked;
		e.preventDefault();
	}

	/**
	 *
	 * @param {HTMLInputElement} checkbox
	 * @param e
	 */
	onMouseMove(checkbox, e) {
		if (this.selecting !== null) {
			if (checkbox.checked !== this.selecting) {
				checkbox.click();
			}
			e.preventDefault();
		}
	}

	onMouseUp(checkbox, e) {
		if (this.selecting !== null) {
			this.selecting = null;
			e.preventDefault();
		}
	}

	/**
	 * @param {Number[]} ids
	 */
	deselect(ids) {
		for (let id of ids)this.ids.remove(id);
	}

	destroy() {
		delete window.grids[this.element.id];
	}

	getSelects() {
		return this.element.querySelectorAll(EntityGrid.CHECKBOX_ROW_SELECTOR);
	}

	select(checkbox) {
		const value = checkbox.dataset.rowSelect, checked = checkbox.checked;
		if (value === 'all') {
			for (let e of this.getSelects()) {
				e.checked = checked;
				this.select(e);
			}
		} else {
			this.ids.set(value, checked);
		}
	}

	/**
	 * @param {HTMLAnchorElement} a
	 * @param {Event} e
	 * @returns {boolean}
	 */
	handleGroupAction(a, e) {
		console.log('handleGroupAction super');

	}

	/**
	 *
	 * @param {HTMLAnchorElement} a
	 * @param {Event} e
	 * @returns {boolean}
	 */
	handleGridSelection(a, e) {
		console.log('handleGridSelection',a,e);
		e.preventDefault();
		e.stopPropagation();
		const url = a.href, type = a.dataset.gridSelection;
		switch (type) {
			case'clean':
				this.ids.clear();
				this.updateCheckboxes();
				this.update();
				break;
			case 'select-search':
				qwest.map('post', url, null, null).then((xhr, response)=> {
					for (let id of response)this.ids.add(id);
					this.updateCheckboxes();
					this.update();
				});
				break;
			case 'unselect-search':
				qwest.map('post', url, null, null).then((xhr, response)=> {
					for (let id of response)this.ids.remove(id);
					this.updateCheckboxes();
					this.update();
				});
				break;
		}
		return false;
	}

	updateCheckboxes() {
		for (let e of this.getSelects()) e.checked = this.ids.has(e.dataset.rowSelect);
	}

	init() {
		for (let a of this.element.querySelectorAll('a[data-grid-selection]')) {
			a.addEventListener('click', this.handleGridSelection.bind(this, a));
		}
		for (let a of this.element.querySelectorAll('a[data-control*=-groupAction-]')) {
			a.addEventListener('click', this.handleGroupAction.bind(this, a));
		}
		for (let e of this.getSelects()) {
			const cell = e.closest('.grid-cell');
			cell.addEventListener('mousedown', this.onMouseDown.bind(this, e));
			cell.addEventListener('mousemove', this.onMouseMove.bind(this, e));
			cell.addEventListener('mouseup', this.onMouseUp.bind(this, e));
			e.checked = this.ids.has(e.dataset.rowSelect);
		}
		this.update();
	}

	count = true;

	update() {
		const selection = this.element.querySelector('[data-grid-selection]');
		const counters = this.element.querySelectorAll('[data-grid-selection-count]');
		const count = this.ids.size();
		for (let c of counters)c.textContent = count;
		if (Boolean(this.count) !== Boolean(count)) {
			for (let a of this.element.querySelectorAll('a[data-control*=-groupAction-]')) a.classList[count ? 'remove' : 'add']('disabled');
		}
		this.count = count;
		const checked = this.element.querySelectorAll(EntityGrid.CHECKBOX_ROW_SELECTOR + ':checked').length;
		const notChecked = this.element.querySelectorAll(EntityGrid.CHECKBOX_ROW_SELECTOR + ':not(:checked)').length;
		const all = this.element.querySelectorAll(EntityGrid.CHECKBOX_ROW_SELECTOR).length;
		if (checked === all) {
			this.element.querySelector(EntityGrid.CHECKBOX_ALL_SELECTOR).checked = true;
		} else if (notChecked === all) {
			this.element.querySelector(EntityGrid.CHECKBOX_ALL_SELECTOR).checked = false;
		}
	}

	onChange(event) {
		const checkbox = event.target;
		if (checkbox.matches(EntityGrid.CHECKBOX_SELECTOR)) {
			this.select(checkbox);
			this.update();
		}
	}

	/**
	 *
	 * @param {MutationRecord[]} mutationsList
	 * @param {MutationObserver} observer
	 */
	observe(mutationsList, observer) {
		for (var mutation of mutationsList) {
			if (mutation.type == 'childList') {
				if (mutation.target.matches('.grid-table-body,.grid-header,.grid-search-row')) {
					this.init();
				}
			}
		}
	}
}


class GridExtension {
	/**
	 * @type {Naja}
	 */
	naja;

	/**
	 *
	 * @param {Naja} naja
	 */
	constructor(naja) {
		this.naja = naja;
		naja.addEventListener('load', this.initGrids.bind(this));
		naja.addEventListener('success', this.success.bind(this));
		naja.addEventListener('before', this.before.bind(this));
		naja.addEventListener('interaction', this.interaction.bind(this));
	}

	before(event) {
		const {options} = event;
		if (options.groupAction && !event.defaultPrevented) {
			const settings = options.groupAction;
			delete options.groupAction;
			this.naja.makeRequest('POST',settings.url, settings.data, options);
			event.preventDefault();
			return false;
		}
	}

	interaction(event) {
		const {element, options} = event;
		if (element.matches(EntityGrid.GRID_SELECTOR+' [data-grid-selection]')) {
			event.preventDefault();
			event.stopPropagation();
			return false;
		}else if (element.matches(EntityGrid.GRID_SELECTOR+' .grid-group-actions a')) {
			options.groupAction = {
				url: element.dataset.url || element.href,
				data: {
					ids: element.closest(EntityGrid.GRID_SELECTOR).dataGrid.ids.values()
				}
			};
		}
	}

	initGrids() {
		for (let e of document.querySelectorAll(EntityGrid.GRID_SELECTOR)) e.dataGrid || new EntityGrid(e);
	}

	success({response}) {
		if (response && response.grid) {
			for (var id in response.grid) {
				if (response.grid.hasOwnProperty(id)) {
					const gridElement = document.getElementById(id);
					if (gridElement) {
						const grid = gridElement.dataGrid;
						const actions = response.grid[id];
						for (var action in actions) {
							if (actions.hasOwnProperty(action) && typeof grid[action] === 'function') {
								grid[action](actions[action]);
							}
						}
					}
				}
			}
		}
	}
}

export {GridLocalStorage, EntityGrid, GridExtension};