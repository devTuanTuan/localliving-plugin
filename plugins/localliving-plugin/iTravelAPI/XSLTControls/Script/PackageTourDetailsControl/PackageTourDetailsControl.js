


// funkcija zadanom tabu postavlja klasu "selected" kako bi ga funkcija za selektiranje taba prikazala!
function selectTab(tabName)
{
    if (tabName != '')
    {
        var tab = document.getElementById(tabName);
        if (tab != null)
        {
            tab.className = "selected";
        }
    }
}