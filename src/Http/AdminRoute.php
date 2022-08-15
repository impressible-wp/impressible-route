<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

class AdminRoute
{
    /**
     * (Required for SubMenu)
     * The slug name for the parent menu (or the file name of a standard
     * WordPress admin page).
     *
     * @var string
     */
    protected $parent_slug;

    /**
     * (Required)
     * The text to be displayed in the title
     * tags of the page when the menu is selected.
     *
     * @var string
     */
    protected $page_title;

    /**
     * (Required)
     * The text to be used for the menu.
     *
     * @var string
     */
    protected $menu_title;

    /**
     * The capability required for this menu to be displayed to the user.
     *
     * @var string
     */
    protected $capability;

    /**
     * (Required)
     * The slug name to refer to this menu by. Should be unique for this
     * menu page and only include lowercase alphanumeric, dashes, and
     * underscores characters to be compatible with sanitize_key().
     *
     * @var string
     */
    protected $menu_slug;

    /**
     * The function to be called to output the content
     * for this page.
     *
     * Default value: ''
     *
     * @var callable|string
     */
    protected $callback = '';

    /**
     * The URL to the icon to be used for this menu.
     *
     * @see add_menu_page()
     * @var string
     */
    protected $icon_url = '';

    /**
     * The position in the menu order this item should
     * appear.
     *
     * @var int|float|nul
     */
    protected $position = null;

    /**
     * Constructor.
     *
     * @param string $parent_slug
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param string $callback
     * @param string $icon_url
     * @param integer|null $position
     */
    public function __construct(
        string $parent_slug,
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        string $icon_url = '',
        ?int $position = null
    )
    {
        $this->parent_slug = $parent_slug;
        $this->page_title = $page_title;
        $this->menu_title = $menu_title;
        $this->capability = $capability;
        $this->menu_slug = $menu_slug;
        $this->callback = $callback;
        $this->icon_url = $icon_url;
        $this->position = $position;
    }

    /**
     * Route for menu link.
     *
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param string $callback
     * @param string $icon_url
     * @param integer|null $position
     *
     * @return AdminRoute
     */
    public static function menu(
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        string $icon_url = '',
        ?int $position = null
    ): AdminRoute
    {
        return new static(
            '',
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback,
            $icon_url,
            $position
        );
    }

    /**
     * Route for submenu link.
     *
     * @param string $parent_slug
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param string $callback
     * @param integer|null $position
     *
     * @return AdminRoute
     */
    public static function subMenu(
        string $parent_slug,
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        ?int $position = null
    ): AdminRoute
    {
        return new static(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback,
            '',
            $position
        );
    }

    /**
     * If the route is a menu route.
     *
     * If return true, the route should be handled as first level
     * menu link.
     *
     * @return boolean
     */
    public function isMenu(): bool
    {
        return empty($this->parent_slug);
    }

    /**
     * If the route is a submenu route.
     *
     * If return true, the route should be handled as second level
     * menu link.
     *
     * @return boolean
     */
    public function isSubMenu(): bool
    {
        return !$this->isMenu();
    }

    public function getParentSlug(): string
    {
        return $this->parent_slug;
    }

    public function getPageTitle(): string
    {
        return $this->page_title;
    }

    public function getMenuTitle(): string
    {
        return $this->menu_title;
    }

    public function getCapability(): string
    {
        return $this->capability;
    }

    /**
     * Return the menu_slug of the route.
     *
     * @return string
     */
    public function getMenuSlug(): string
    {
        return $this->menu_slug;
    }

    public function getIconUrl(): string
    {
        return $this->icon_url;
    }

    /**
     * Get the callback function name or any callable.
     * Returns null if not set.
     *
     * @return callable|null
     */
    public function getCallback()
    {
        if ($this->callback === '') {
            return null;
        }
        if (!is_callable($this->callback)) {
            throw new \Exception('Callback is not a callable: ' . var_export($this->callback, true));
        }
        return $this->callback;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }
}
