..  php:namespace::  TYPO3\CMS\Backend\RecordList\Event

..  php:class:: ModifyRecordListTableActionsEvent

    An event to modify the multi record selection actions (e.g.

    "edit", "copy to clipboard") for a table in the RecordList.

    ..  php:method:: hasAction(string $actionName)
        :returns: `bool`

        Whether the action exists

        :param $actionName: the actionName

    ..  php:method:: getAction(string $actionName)
        :returns: `string|null`

        Get action by its name

        :param $actionName: the actionName
        :Return description: The action or NULL if the action does not exist

    ..  php:method:: removeAction(string $actionName)
        :returns: `bool`

        Remove action by its name

        :param $actionName: the actionName
        :Return description: Whether the action could be removed - Will thereforereturn FALSE if the action to remove does not exist.

