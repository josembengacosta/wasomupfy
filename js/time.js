
// <!-- Script da função da data e hora para emissão de recibos     -->

 function pdf() {
        document.getElementById("data_processo").innerHTML = dataActual();
    }
    
    function dataActual() {
        let data = new Date();
        return dayName[data.getDay()] + ", " + data.getDate() + " de " + monName[data.getMonth()] + " de " + data
            .getFullYear();
    }
    const dayName = [
        "domingo",
        "segunda-feira",
        "terça-feira",
        "quarta-feira",
        "quinta-feira",
        "sexta-feira",
        "sábado",
    ];
    const monName = [
        "Janeiro",
        "Fevereiro",
        "Março",
        "Abril",
        "Maio",
        "Junho",
        "Agosto",
        "Outubro",
        "Novembro",
        "Dezembro",
    ];
    function clock() {

        let hours = document.getElementById('hour');
        let minutes = document.getElementById('minutes');
        let seconds = document.getElementById('seconds');
        let ampm = document.getElementById('ampm');

        let h = new Date().getHours();
        let m = new Date().getMinutes();
        let s = new Date().getSeconds();
        var am = "AM";

        //Convert 24 Hour Time To 12 Hour with AM PM Indicator
        if (h > 24) {
            h = h - 24;
            var am = "PM"
        }

        //Add 0 to the beginning of number if less than 10
        h = (h < 10) ? "0" + h : h
        m = (m < 10) ? "0" + m : m
        s = (s < 10) ? "0" + s : s

        hours.innerHTML = h;
        minutes.innerHTML = m;
        seconds.innerHTML = s;
        ampm.innerHTML = am

    }
    var interval = setInterval(clock, 1000);