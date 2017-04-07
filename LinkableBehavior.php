<?php
/**
 * @link https://github.com/locustv2/yii2-linkable-behavior
 * @copyright Copyright (c) 2017 locustv2
 * @license https://github.com/locustv2/yii2-linkable-behavior/blob/master/LICENSE.md
 */

namespace locustv2\behaviors;

use yii;
use yii\base\Behavior;
use yii\base\Model;
use yii\base\Component;
use yii\base\InvalidRouteException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Html;
use Closure;

/**
 * LinkableBehavior provides the ability to automatically obtain the url that points to
 * a page related to the model. It helps to centralize the routes so that the user does not
 * have to go through all files while making changes.
 *
 * To use LinkableBehavior, configure the [[route]] property which should specify the route
 * without the action. For example a basic controller route `/user/view` should be just `/user`
 * and a module route `/product/review/view` would be `/product/review`.
 * The [[defaultParams]] property should also be configured as an array of parameters to be used
 * when creating the url.
 * For example:
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => \weblement\yii2\behaviors\LinkableBehavior::className(),
 *             'route' => '/article',
 *             'defaultParams' => function ($record) {
 *                 return [
 *                     'id' => $record->id,
 *                     'slug' => $record->slug
 *                 ];
 *             },
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Yuv Joodhisty <locustv2@gmail.com>
 * @since 1.0
 */
class LinkableBehavior extends Behavior
{
    /**
     * @var string the default action to use when creating the urls. By default the
     * action `view` is used.
     */
    public $defaultAction = 'view';
    /**
     * @var bool whether to enable hotlinking to the component. If `true`, an html
     * `span` tag will be returned with the [[hotlinkTextAttr]].
     */
    public $disableHotlink = false;
    /**
     * @var string the attribute to use as text for hotlinks. If not set, the absolute
     * url of the hotlink will be used
     */
    public $hotlinkTextAttr = null;
    /**
     * @var bool whether to use absolute urls when using hotlinks
     */
    public $useAbsoluteUrl = false;
    /**
     * @var string the route to use for the component routes. It should not contain
     * the action
     */
    private $_route = null;
    /**
     * @var array|\closure the default parameters to use when creating the route. If not set
     * the component class name will be parsed and used instead.
     */
    private $_defaultParams = [];
    /**
     * @var array|\closure the parameters to use when linking to this component. If not set
     * the default parameters will be prefixed and used instead.
     */
    private $_linkableParams = [];


    /**
     * @param string $route route
     */
    public function setRoute(string $route)
    {
        return $this->_route = $route;
    }

    /**
     * @return string route
     */
    public function getRoute()
    {
        return strtr('/{route}', [
            '{route}' => is_null($this->_route)
                ? strtolower(Inflector::pluralize(StringHelper::baseName($this->owner->className())))
                : trim($this->_route, '/'),
        ]);
    }

    /**
     * @param array|\closure $params default parameters
     */
    public function setDefaultParams($params)
    {
        $this->_defaultParams = $params;
    }

    /**
     * @return array default parameters
     */
    public function getDefaultParams()
    {
        return $this->parseParams($this->_defaultParams);
    }

    /**
     * @param array|\closure $params linkable parameters
     */
    public function setLinkableParams($params)
    {
        $this->_linkableParams = $params;
    }

    /**
     * @return array linkable parameters
     */
    public function getLinkableParams()
    {
        $parsedLinkableParams = $this->parseParams($this->_linkableParams);

        if(empty($parsedLinkableParams)) {
            $parsedLinkableParams = $this->defaultParams;
            $prefix = strtolower(substr(StringHelper::baseName($this->owner->className()), 0, 1));
            foreach ($parsedLinkableParams as $key => $value) {
                $parsedLinkableParams[$prefix.$key] = $value;
                unset($parsedLinkableParams[$key]);
            }
        }

        return $parsedLinkableParams;
    }

    /**
     * Returns the url route which can be used across yii.
     * Example (assuming that you use prettyUrl):
     * ```php
     * use yii\helpers\Url;
     *
     * // /component/view?id=12345
     * echo Url::to($component->urlRoute);
     *
     * // /component/update?id=12345
     * echo Url::to($component->getUrlRoute('update'));
     *
     * // http://www.yoursite.com/component/profile?id=12345&ref=facebook
     * echo Url::to($component->getUrlRoute('profile', ['ref' => 'facebook']), true);
     * ```
     * @param string $action the action to use if you want to override [[defaultAction]]
     * @param array $params additional parameters to use beside [[defaultParams]]. Can also be used
     * to override [[defaultParams]]
     * @return array the url route
     */
    public function getUrlRoute($action = null, array $params = [])
    {
        $route = strtr('{route}/{action}', [
            '{route}' => $this->route,
            '{action}' => $action ?: $this->defaultAction,
        ]);

        return ArrayHelper::merge([$route], $this->defaultParams, $params);
    }

    /**
     * Returns the url route which links to another component that uses LinkableBehavior
     * Example (assuming that you use prettyUrl):
     * ```php
     * $user = User::findOne(['id' => 123]);
     * $photo = Photo::findOne(['userId' => 123]); // photo id is 456
     *
     * // /user/photo/view?id=123&pid=456
     * echo Url::to($user->getUrlRouteTo($photo));
     *
     * // /photo/user/view?id=456&uid=123
     * echo Url::to($photo->getUrlRouteTo($user));
     *
     * // /user/photo/update?id=123&pid=456
     * echo Url::to($user->getUrlRouteTo($photo, 'update'));
     * ```
     * @param Component $component the component to link to
     * @param string $action the action to use if you want to override [[defaultAction]] in the component
     * @return array the url route which links to the component
     */
    public function getUrlRouteTo(Component $component, $action = null)
    {
        if(!ArrayHelper::isIn($this->className(), ArrayHelper::getColumn($component->behaviors(), 'class'))) {
            throw new InvalidRouteException('The "LinkableBehavior" is not attached to the specified component');
        }

        return $this->getUrlRoute(strtr('{route}/{action}', [
            '{route}' => trim($component->route, '/'),
            '{action}' => $action ?: $component->defaultAction
        ]), $component->linkableParams);
    }

    /**
     * Returns the hotlink to this component route. If [[disableHotlink]] is true, an
     * html span will be used instead of html anchor tag
     * The hotlink will be created using [[getUrlRoute()]] with the display text from [[hotlinkTextAttr]]
     * @param string $action the action to use if you want to override [[defaultAction]]
     * @param array $params additional parameters to use. @see [[getUrlRoute]]
     * @param array @options the html options to use when rendering the link.
     * @return string the hotlink to this component
     */
    public function getHotlink($action = null, array $params = [], array $options = [])
    {
        $text = is_null($this->hotlinkTextAttr)
            ? Url::to($this->getUrlRoute($action, $params), $this->useAbsoluteUrl)
            : ArrayHelper::getValue($this->owner, $this->hotlinkTextAttr);

        return $this->disableHotlink
            ? Html::tag('span', $text, $options)
            : Html::a($text, $this->getUrlRoute($action, $params), $options);
    }

    /**
     * Returns the hotlink to the linke component route. If [[disableHotlink]] is true, an
     * html span will be used instead of html anchor tag
     * The hotlink will be created using [[getUrlRouteTo()]] with the display text from [[hotlinkTextAttr]]
     * @param Component $component the component that you want to link to
     * @param string $action the action to use if you want to override [[defaultAction]]
     * @param array $params additional parameters to use. @see [[getUrlRoute]]
     * @param array $options the html options to use when rendering the link.
     * @return string the hotlink to the linked component used.
     */
    public function getHotlinkTo(Component $component, $action = null, array $params = [], array $options = [])
    {
        if(!ArrayHelper::isIn($this->className(), ArrayHelper::getColumn($component->behaviors(), 'class'))) {
            throw new InvalidRouteException('The "LinkableBehavior" is not attached to the specified component');
        }

        return $this->getHotlink(strtr('{route}/{action}', [
            '{route}' => trim($component->route, '/'),
            '{action}' => $action ?: $component->defaultAction
        ]), ArrayHelper::merge($component->linkableParams, $params), $options);
    }

    /**
     * Parses the params to ensure correct parameters are used
     * @param array|closure the param to be parsed. If a closure is passed, it will
     * be applied on the owner of this behavior.
     * @return array the list of parameters
     */
    protected function parseParams($params)
    {
        if($params instanceof \closure) {
            $closure = $params;
            return $closure($this->owner);
        }

        return $params;
    }
}
