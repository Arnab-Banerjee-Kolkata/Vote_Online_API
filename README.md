# Vote_Online_API

<b>Election status::</b><br><br>
    0: pending<br>
    1: live<br>
    2: over but result pending<br>
    3: over and result declared<br>
    4: cancelled<br>
<br>
<br>
<b>ValidateOtp category::</b><br><br>
    voter: When voter's OTP is needed to be validated from Remote Polling Device<br>
    booth: When booth's OTP is needed to be validated from Voter's device<br>
<br>
<br>
<br>
<b>INSTRUCTIONS TO CLONE THIS REPO</b><br>
<br>
Clone this to your machine repo with the help of steps from here: https://www.cocoanetics.com/2011/03/cloning-a-git-repo-with-submodules/<br>
<br>
<br>
<b>INSTRUCTIONS FOR COLLABORATORS TO ADD/UPDATE USAGE FILES OF CORRESPONDING API END-POINTS</b><br><br>1. Upload/ update the usage file(s) to this repo: https://github.com/Arnab-Banerjee-Kolkata/Vote_API_Usages<br>
<br>
2. In your local repo, run the following commands.<br>
<br>
      git submodule foreach git pull origin master<br>
      git commit -am "Your commit message"<br>
      git push origin master<br>
