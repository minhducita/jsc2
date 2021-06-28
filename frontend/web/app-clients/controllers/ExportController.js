/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
app.controller("ExportController", ['$rootScope', '$scope', 'AppService', function(a, b, c) {
    b.selectedBoard = 0;
    b.selectedCard = [];
    b.selectedMember = [];
    b.listBoardReport = [];
    b.listCardIdBoard = [];
    b.listMemberIdBoard = [];
    b.boardPageAssets = [];

    c.boards.getListBoardReport()
        .then(response => {
            var startDate = angular.element($('#startDate')).val();
            var endDate = angular.element($('#endDate')).val();
            b.listBoardReport.push({'id': 0, 'displayName': 'Choose board'});
            b.listBoardReport.push({'id': -1, 'displayName': 'All board'});
            response.forEach(element => {
                b.listBoardReport.push({'id': element.id, 'displayName': element.displayName});
            });
        });

    b.onExport = function($event) {
        var startDate = angular.element($('#startDate')).val();
        var endDate = angular.element($('#endDate')).val();
        var type = $event;

        if (b.selectedCard.length == 0) {
            b.listCardIdBoard.forEach(element => {
                b.selectedCard.push(element.id);
            });
        }

        if (b.selectedMember.length != 0) {
            c.export.exportExcel(startDate, endDate, b.selectedBoard, b.selectedCard, b.selectedMember, type);
        } else {
            alert("Please choosen member");
        }
    };
    
    b.onRefresh = function () {
        window.location.reload();
    };

    b.onChangeBoard = function() {
        var startDate = angular.element($('#startDate')).val();
        var endDate = angular.element($('#endDate')).val();
        b.listCardIdBoard = [];
        b.listMemberIdBoard = [];

        c.cards.getListCardIdBoard(b.selectedBoard, startDate, endDate)
            .then(response => {
                response.forEach(element => {
                    b.listCardIdBoard.push({'id': element.idCard, 'displayName': element.displayName});
                });
            });

        c.members.getListMemberIdBoard(b.selectedBoard, startDate, endDate)
            .then(response => {
                response.forEach(element => {
                    b.listMemberIdBoard.push({'id': element.idMember, 'username': element.nameMember});
                });
            });
    };
}]);